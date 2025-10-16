<?php

declare(strict_types=1);

namespace App\Application\Order\CreateOrder;

use App\Application\Payment\ProcessPayment\ProcessPaymentCommand;
use App\Application\Payment\ProcessPayment\ProcessPaymentUseCase;
use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentStatus;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\LineTotal;
use App\Domain\Order\ValueObject\OrderNumber;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use App\Domain\Shared\Exceptions\ProductNotFoundException;
use App\Domain\Shared\TransactionManagerInterface;
use App\Domain\Product\Repository\StockReservationRepositoryInterface;
use App\Infrastructure\Queue\Jobs\ConfirmStockReservationJob;
use App\Infrastructure\Queue\Jobs\ReleaseStockReservationJob;

final readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
        private TransactionManagerInterface $transactionManager,
        private ProcessPaymentUseCase $processPaymentUseCase,
        private StockReservationRepositoryInterface $stockReservationRepository,
    ) {
    }

    public function execute(CreateOrderCommand $command): OrderData
    {
        // Optimistic locking with retry (max 3 attempts)
        $maxRetries = 3;
        $retryDelay = 50000; // 50ms in microseconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->attemptCreateOrder($command);
            } catch (InsufficientStockException $e) {
                // If last attempt, throw exception
                if ($attempt === $maxRetries) {
                    throw $e;
                }
                // Otherwise, wait and retry
                usleep($retryDelay * $attempt); // Exponential backoff
            }
        }

        throw InsufficientStockException::forProduct($command->quantity, 0);
    }

    private function attemptCreateOrder(CreateOrderCommand $command): OrderData
    {
        $result = $this->transactionManager->transaction(function () use ($command) {
            $productEntity = $this->productRepository->findById($command->productId);

            if (!$productEntity) {
                throw ProductNotFoundException::withId($command->productId);
            }

            $product = [
                'id' => $productEntity->id,
                'name' => $productEntity->name,
                'price' => $productEntity->price,
                'stock' => $productEntity->stock,
            ];

            // 2. Calculate amounts (Domain logic)
            $lineTotal = LineTotal::calculate($product['price'], $command->quantity);

            // 3. Generate order number (Domain logic)
            $orderNumber = OrderNumber::generate();

            // 4. Optimistically decrement stock (atomic operation)
            $stockDecremented = $this->productRepository->decrementStockOptimistic(
                $product['id'],
                $command->quantity
            );

            if (!$stockDecremented) {
                throw InsufficientStockException::forProduct(
                    $command->quantity,
                    0
                );
            }

            // 5. Create order (status: PENDING)
            $orderId = $this->orderRepository->create([
                'user_id' => $command->userId,
                'order_number' => $orderNumber->value,
                'status' => OrderStatus::PENDING->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'total_amount' => $lineTotal->total,
            ]);

            // 6. Create stock reservation (for tracking purposes)
            $this->stockReservationRepository->create(
                orderId: $orderId,
                productId: $product['id'],
                quantity: $command->quantity
            );

            // 7. Create order items
            $this->orderRepository->createOrderItems($orderId, [
                [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'price' => $lineTotal->unitPrice,
                    'quantity' => $lineTotal->quantity,
                    'line_total' => $lineTotal->total,
                ],
            ]);

            // 8. Process payment
            $paymentResult = $this->processPaymentUseCase->execute(
                new ProcessPaymentCommand(
                    orderId: $orderId,
                    amount: $lineTotal->total,
                    method: $command->paymentMethod,
                    metadata: [
                        'user_id' => $command->userId,
                        'product_id' => $product['id'],
                    ]
                )
            );

            // 9. Update order based on payment result
            if ($paymentResult->success) {
                $this->orderRepository->update($orderId, [
                    'status' => OrderStatus::COMPLETED->value,
                    'payment_status' => PaymentStatus::PAID->value,
                ]);
            } else {
                $this->orderRepository->update($orderId, [
                    'status' => OrderStatus::FAILED->value,
                    'payment_status' => PaymentStatus::FAILED->value,
                ]);
            }

            // 10. Return data for async stock confirmation/release
            $orderData = $this->orderRepository->findById($orderId);
            return [
                'orderData' => OrderData::fromArray($orderData),
                'paymentSuccess' => $paymentResult->success,
            ];
        });

        // 11. After transaction commit: Confirm or Release reservation based on payment result
        if ($result['paymentSuccess']) {
            ConfirmStockReservationJob::dispatch(
                orderId: $result['orderData']->id
            )->onQueue('stock');
        } else {
            ReleaseStockReservationJob::dispatch(
                orderId: $result['orderData']->id
            )->onQueue('stock');
        }

        return $result['orderData'];
    }
}
