<?php

declare(strict_types=1);

namespace App\Application\Order\CreateOrder;

final readonly class OrderData
{
    public function __construct(
        public int $id,
        public string $orderNumber,
        public string $status,
        public string $paymentStatus,
        public float $totalAmount,
        public array $items,
        public string $createdAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            orderNumber: $data['order_number'],
            status: $data['status'],
            paymentStatus: $data['payment_status'],
            totalAmount: $data['total_amount'],
            items: $data['items'] ?? [],
            createdAt: $data['created_at'],
        );
    }
}
