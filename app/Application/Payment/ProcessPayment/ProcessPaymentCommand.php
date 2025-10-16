<?php

declare(strict_types=1);

namespace App\Application\Payment\ProcessPayment;

use App\Domain\Payment\Enums\PaymentMethod;

final readonly class ProcessPaymentCommand
{
    public function __construct(
        public float $amount,
        public PaymentMethod $method,
        public array $metadata = [],
    ) {
    }
}
