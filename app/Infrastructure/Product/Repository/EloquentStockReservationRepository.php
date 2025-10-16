<?php

declare(strict_types=1);

namespace App\Infrastructure\Product\Repository;

use App\Domain\Product\Enums\ReservationStatus;
use App\Domain\Product\Repository\StockReservationRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentStockReservationRepository implements StockReservationRepositoryInterface
{
    public function create(int $orderId, int $productId, int $quantity): int
    {
        return DB::table('stock_reservations')->insertGetId([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'status' => ReservationStatus::PENDING->value,
            'expires_at' => now()->addMinutes(10), // 10 minutes expiration
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function findByOrderId(int $orderId): ?array
    {
        $reservation = DB::table('stock_reservations')
            ->where('order_id', $orderId)
            ->first();

        return $reservation ? (array) $reservation : null;
    }

    public function confirm(int $reservationId): void
    {
        DB::table('stock_reservations')
            ->where('id', $reservationId)
            ->update([
                'status' => ReservationStatus::CONFIRMED->value,
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function release(int $reservationId): void
    {
        DB::table('stock_reservations')
            ->where('id', $reservationId)
            ->update([
                'status' => ReservationStatus::RELEASED->value,
                'released_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function expire(int $reservationId): void
    {
        DB::table('stock_reservations')
            ->where('id', $reservationId)
            ->update([
                'status' => ReservationStatus::EXPIRED->value,
                'updated_at' => now(),
            ]);
    }

    public function updateStatus(int $reservationId, ReservationStatus $status): void
    {
        DB::table('stock_reservations')
            ->where('id', $reservationId)
            ->update([
                'status' => $status->value,
                'updated_at' => now(),
            ]);
    }

    public function getExpiredReservations(): array
    {
        return DB::table('stock_reservations')
            ->where('status', ReservationStatus::PENDING->value)
            ->where('expires_at', '<', now())
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    public function getTotalReservedQuantity(int $productId): int
    {
        // Only PENDING reservations hold stock
        // CONFIRMED reservations already decremented from products.stock
        return (int) DB::table('stock_reservations')
            ->where('product_id', $productId)
            ->where('status', ReservationStatus::PENDING->value)
            ->sum('quantity');
    }
}
