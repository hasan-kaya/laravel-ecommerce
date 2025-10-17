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
            } catch (InsufficientStockException | \RuntimeException $e) {
                // If last attempt, throw exception
                if ($attempt === $maxRetries) {
                    if ($e instanceof \RuntimeException) {
                        throw InsufficientStockException::forProduct($command->quantity, 0);
                    }
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
        // STEP 1: Create order in transaction (fast DB operations only)
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

            // 4. Create order (status: PENDING - Two-Phase Commit)
            $orderId = $this->orderRepository->create([
                'user_id' => $command->userId,
                'order_number' => $orderNumber->value,
                'status' => OrderStatus::PENDING->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'total_amount' => $lineTotal->total,
            ]);

            // 5. Create stock reservation (atomic check + lock)
            // This will throw RuntimeException if insufficient stock
            $this->stockReservationRepository->create(
                orderId: $orderId,
                productId: $product['id'],
                quantity: $command->quantity
            );

            // 6. Create order items
            $this->orderRepository->createOrderItems($orderId, [
                [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'price' => $lineTotal->unitPrice,
                    'quantity' => $lineTotal->quantity,
                    'line_total' => $lineTotal->total,
                ],
            ]);

            // Return order data for payment processing
            return [
                'orderId' => $orderId,
                'lineTotal' => $lineTotal,
                'productId' => $product['id'],
            ];
        });

        // STEP 2: Process payment OUTSIDE transaction
        $paymentResult = $this->processPaymentUseCase->execute(
            new ProcessPaymentCommand(
                orderId: $result['orderId'],
                amount: $result['lineTotal']->total,
                method: $command->paymentMethod,
                metadata: [
                    'user_id' => $command->userId,
                    'product_id' => $result['productId'],
                ]
            )
        );

        // STEP 3: Update order based on payment result (separate transaction)
        $this->transactionManager->transaction(function () use ($result, $paymentResult) {
            if ($paymentResult->success) {
                $this->orderRepository->update($result['orderId'], [
                    'status' => OrderStatus::COMPLETED->value,
                    'payment_status' => PaymentStatus::PAID->value,
                ]);
            } else {
                $this->orderRepository->update($result['orderId'], [
                    'status' => OrderStatus::FAILED->value,
                    'payment_status' => PaymentStatus::FAILED->value,
                ]);
            }
        });

        // STEP 4: Get final order data
        $orderData = $this->orderRepository->findById($result['orderId']);

        // STEP 5: Dispatch async job for stock reservation (Saga compensation)
        if ($paymentResult->success) {
            ConfirmStockReservationJob::dispatch(
                orderId: $result['orderId']
            )->onQueue('stock');
        } else {
            ReleaseStockReservationJob::dispatch(
                orderId: $result['orderId']
            )->onQueue('stock');
        }

        return OrderData::fromArray($orderData);
    }
}
