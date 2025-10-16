<?php

declare(strict_types=1);

namespace App\Domain\Payment;

use App\Domain\Payment\Enums\PaymentMethod;

/**
 * Payment Service Factory Interface (Domain Contract)
 * 
 * Defines contract for payment service factory
 * Infrastructure provides implementation
 */
interface PaymentServiceFactoryInterface
{
    /**
     * Get payment service for given method
     * 
     * @param PaymentMethod $method
     * @return PaymentServiceInterface
     * @throws \RuntimeException If service not registered
     */
    public function get(PaymentMethod $method): PaymentServiceInterface;

    /**
     * Check if payment method is supported
     * 
     * @param PaymentMethod $method
     * @return bool
     */
    public function has(PaymentMethod $method): bool;

    /**
     * Get all available payment methods
     * 
     * @return array<PaymentMethod>
     */
    public function getAvailableMethods(): array;
}
