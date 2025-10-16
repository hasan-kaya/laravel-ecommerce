<?php

declare(strict_types=1);

namespace App\Domain\Product\Repository;

use App\Domain\Product\Enums\ReservationStatus;

interface StockReservationRepositoryInterface
{
    /**
     * Create a new stock reservation
     */
    public function create(int $orderId, int $productId, int $quantity): int;

    /**
     * Find reservation by order ID
     */
    public function findByOrderId(int $orderId): ?array;

    /**
     * Confirm reservation (convert to actual stock decrement)
     */
    public function confirm(int $reservationId): void;

    /**
     * Release reservation (return stock)
     */
    public function release(int $reservationId): void;

    /**
     * Mark reservation as expired
     */
    public function expire(int $reservationId): void;

    /**
     * Update reservation status
     */
    public function updateStatus(int $reservationId, ReservationStatus $status): void;

    /**
     * Get expired reservations
     */
    public function getExpiredReservations(): array;

    /**
     * Get total reserved quantity for a product (PENDING only)
     * CONFIRMED reservations already decremented from actual stock
     * Used to calculate available stock
     */
    public function getTotalReservedQuantity(int $productId): int;
}
