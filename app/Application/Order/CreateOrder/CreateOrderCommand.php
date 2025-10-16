<?php

declare(strict_types=1);

namespace App\Application\Order\CreateOrder;

final readonly class CreateOrderCommand
{
    public function __construct(
        public int $userId,
        public int $productId,
        public int $quantity,
    ) {
    }
}
