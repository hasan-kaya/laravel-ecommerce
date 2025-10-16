<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Domain\Payment\PaymentServiceInterface;

/**
 * Fake Iyzico Payment Service
 * 
 * Simulates Iyzico payment gateway for development/testing
 * In production, this would call real Iyzico API
 */
final readonly class FakeIyzicoPaymentService implements PaymentServiceInterface
{
    public function process(float $amount, array $metadata = []): array
    {
        // Simulate API call delay
        usleep(100000); // 100ms

        // Simulate 90% success rate
        $isSuccess = rand(1, 100) <= 90;

        if ($isSuccess) {
            return [
                'success' => true,
                'transaction_id' => 'IYZICO-' . uniqid(),
                'message' => 'Payment processed successfully via Iyzico',
            ];
        }

        return [
            'success' => false,
            'transaction_id' => null,
            'message' => 'Insufficient funds or card declined',
        ];
    }

    public function getProviderName(): string
    {
        return 'iyzico';
    }
}
