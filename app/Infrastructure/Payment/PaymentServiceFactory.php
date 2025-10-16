<?php

declare(strict_types=1);

namespace App\Infrastructure\Payment;

use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Payment\Contract\PaymentServiceFactoryInterface;
use App\Domain\Payment\Contract\PaymentServiceInterface;

/**
 * Payment Service Factory (Infrastructure Implementation)
 * 
 * Strategy Pattern implementation
 * Creates appropriate payment service based on payment method
 */
final class PaymentServiceFactory implements PaymentServiceFactoryInterface
{
    /** @var array<string, PaymentServiceInterface> */
    private array $services = [];

    public function register(PaymentMethod $method, PaymentServiceInterface $service): void
    {
        $this->services[$method->value] = $service;
    }

    public function get(PaymentMethod $method): PaymentServiceInterface
    {
        if (!isset($this->services[$method->value])) {
            throw new \RuntimeException(
                "Payment service for method '{$method->value}' is not registered"
            );
        }

        return $this->services[$method->value];
    }

    public function has(PaymentMethod $method): bool
    {
        return isset($this->services[$method->value]);
    }

    /**
     * Get all available payment methods
     * 
     * @return array<PaymentMethod>
     */
    public function getAvailableMethods(): array
    {
        return array_map(
            fn(string $key) => PaymentMethod::from($key),
            array_keys($this->services)
        );
    }
}
