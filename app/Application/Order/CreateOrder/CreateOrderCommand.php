<?php

declare(strict_types=1);

namespace App\Application\Order\CreateOrder;

use App\Domain\Payment\Enums\PaymentMethod;

final readonly class CreateOrderCommand
{
    public function __construct(
        public int $userId,
        public int $productId,
        public int $quantity,
        public PaymentMethod $paymentMethod,
    ) {
    }
}
