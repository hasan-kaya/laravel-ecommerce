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
use App\Domain\Payment\Repository\PaymentRepositoryInterface;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use App\Domain\Shared\Exceptions\ProductNotFoundException;
use App\Domain\Shared\TransactionManagerInterface;

final readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository,
        private TransactionManagerInterface $transactionManager,
        private ProcessPaymentUseCase $processPaymentUseCase,
        private PaymentRepositoryInterface $paymentRepository,
    ) {
    }

    public function execute(CreateOrderCommand $command): OrderData
    {
        return $this->transactionManager->transaction(function () use ($command) {
            // 1. Get product with lock (race condition protection)
            $product = $this->productRepository->findByIdWithLock($command->productId);

            if (!$product) {
                throw ProductNotFoundException::withId($command->productId);
            }

            // 2. Validate stock
            if ($product['stock'] < $command->quantity) {
                throw InsufficientStockException::forProduct(
                    $command->quantity,
                    $product['stock']
                );
            }

            // 3. Calculate amounts (Domain logic)
            $lineTotal = LineTotal::calculate($product['price'], $command->quantity);

            // 4. Decrement stock (via repository - atomic)
            $this->productRepository->decrementStock($command->productId, $command->quantity);

            // 5. Generate order number (Domain logic)
            $orderNumber = OrderNumber::generate();

            // 6. Create order (status: PENDING - will be updated after payment)
            $orderId = $this->orderRepository->create([
                'user_id' => $command->userId,
                'order_number' => $orderNumber->value,
                'status' => OrderStatus::PENDING->value,
                'payment_status' => PaymentStatus::PENDING->value,
                'total_amount' => $lineTotal->total,
            ]);

            // 7. Get attempt number for this order
            $attemptNumber = $this->paymentRepository->getNextAttemptNumber($orderId);

            // 8. Process payment (via ProcessPaymentUseCase - Composition)
            $paymentResult = $this->processPaymentUseCase->execute(
                new ProcessPaymentCommand(
                    orderId: $orderId,
                    amount: $lineTotal->total,
                    method: $command->paymentMethod,
                    attemptNumber: $attemptNumber,
                    metadata: [
                        'user_id' => $command->userId,
                    ]
                )
            );

            // 9. Create order items (before payment check)
            $this->orderRepository->createOrderItems($orderId, [
                [
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'price' => $lineTotal->unitPrice,
                    'quantity' => $lineTotal->quantity,
                    'line_total' => $lineTotal->total,
                ],
            ]);

            // 10. Update order based on payment result
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

            // 11. Return order data
            $orderData = $this->orderRepository->findById($orderId);
            return OrderData::fromArray($orderData);
        });
    }
}
