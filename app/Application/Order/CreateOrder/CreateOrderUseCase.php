<?php

declare(strict_types=1);

namespace App\Application\Order\CreateOrder;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Enums\PaymentStatus;
use App\Domain\Order\OrderRepositoryInterface;
use App\Domain\Order\ValueObject\LineTotal;
use App\Domain\Order\ValueObject\OrderNumber;
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

            // 6. Create order (status: COMPLETED - simplified for now)
            $orderId = $this->orderRepository->create([
                'user_id' => $command->userId,
                'order_number' => $orderNumber->value,
                'status' => OrderStatus::COMPLETED->value,
                'payment_status' => PaymentStatus::PAID->value,
                'total_amount' => $lineTotal->total,
            ]);

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

            // 8. Return order data
            $orderData = $this->orderRepository->findById($orderId);
            return OrderData::fromArray($orderData);
        });
    }
}
