<?php

declare(strict_types=1);

namespace App\Domain\Product\ValueObject;

use App\Domain\Product\Enums\ReservationStatus;

/**
 * Stock Reservation Value Object
 * 
 * Represents a temporary stock hold for an order
 */
final readonly class StockReservation
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public int $orderId,
        public ReservationStatus $status,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    /**
     * Create a new reservation (expires in 30 minutes)
     */
    public static function create(
        int $productId,
        int $quantity,
        int $orderId,
    ): self {
        return new self(
            productId: $productId,
            quantity: $quantity,
            orderId: $orderId,
            status: ReservationStatus::PENDING,
            expiresAt: new \DateTimeImmutable('+30 minutes'),
        );
    }

    /**
     * Check if reservation is expired
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Check if reservation can be confirmed
     */
    public function canConfirm(): bool
    {
        return $this->status === ReservationStatus::PENDING && !$this->isExpired();
    }

    /**
     * Check if reservation can be released
     */
    public function canRelease(): bool
    {
        return $this->status === ReservationStatus::PENDING;
    }
}
