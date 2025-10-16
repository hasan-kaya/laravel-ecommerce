<?php

declare(strict_types=1);

namespace App\Domain\Payment\ValueObject;

use App\Domain\Payment\Enums\PaymentMethod;

/**
 * Payment Result Value Object
 * 
 * Immutable representation of payment processing result
 */
final readonly class PaymentResult
{
    private function __construct(
        public bool $success,
        public ?string $transactionId,
        public string $message,
        public PaymentMethod $method,
    ) {
    }

    public static function success(
        string $transactionId,
        PaymentMethod $method,
        string $message = 'Payment successful'
    ): self {
        return new self(
            success: true,
            transactionId: $transactionId,
            message: $message,
            method: $method,
        );
    }

    public static function failure(
        PaymentMethod $method,
        string $message = 'Payment failed'
    ): self {
        return new self(
            success: false,
            transactionId: null,
            message: $message,
            method: $method,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
            'method' => $this->method->value,
        ];
    }
}
