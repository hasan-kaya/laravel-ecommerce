<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Domain\Payment\Contract\PaymentServiceInterface;

/**
 * Fake Iyzico Payment Service
 *
 * Simulates Iyzico payment gateway for development/testing
 * In production, this would call real Iyzico API
 */
final readonly class IyzicoPaymentService implements PaymentServiceInterface
{
    public function process(float $amount, array $metadata = []): array
    {
        $paymentId = 'pay_' . bin2hex(random_bytes(8)) . '_' . time();

        // Simulate 90% success rate
        $isSuccess = rand(1, 100) <= 90;

        if ($isSuccess) {
            return [
                'success' => true,
                'payment_id' => $paymentId, // Gateway's unique payment identifier (idempotency key)
                'transaction_id' => 'txn_' . bin2hex(random_bytes(8)), // Cryptographically secure transaction ID
                'message' => 'Payment processed successfully via Iyzico',
            ];
        }

        return [
            'success' => false,
            'payment_id' => $paymentId, // Even failed payments have payment_id
            'transaction_id' => null,
            'message' => 'Insufficient funds or card declined',
        ];
    }

    public function getProviderName(): string
    {
        return 'iyzico';
    }
}
