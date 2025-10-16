<?php

declare(strict_types=1);

namespace App\Domain\Payment;

/**
 * Payment Service Interface (Domain Contract)
 * 
 * Strategy Pattern: Multiple payment providers can implement this
 * Clean Architecture: Domain defines contract, Infrastructure implements
 */
interface PaymentServiceInterface
{
    /**
     * Process payment with the payment gateway
     * 
     * @param float $amount Amount to charge
     * @param array $metadata Additional payment metadata (order_id, user_id, etc.)
     * @return array{success: bool, transaction_id: ?string, message: string}
     */
    public function process(float $amount, array $metadata = []): array;

    /**
     * Get payment provider name
     * 
     * @return string Provider identifier (e.g., 'iyzico', 'paytr')
     */
    public function getProviderName(): string;
}
