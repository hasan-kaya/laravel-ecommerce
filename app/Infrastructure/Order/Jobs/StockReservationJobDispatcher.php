<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Jobs;

use App\Domain\Order\Contract\StockReservationJobDispatcherInterface;
use App\Infrastructure\Queue\Jobs\ConfirmStockReservationJob;
use App\Infrastructure\Queue\Jobs\ReleaseStockReservationJob;

/**
 * Stock Reservation Job Dispatcher (Infrastructure Implementation)
 * 
 * Implements the domain interface for dispatching queue jobs.
 * This implementation uses Laravel Queue jobs.
 */
final readonly class StockReservationJobDispatcher implements StockReservationJobDispatcherInterface
{
    public function dispatchConfirmReservation(int $orderId): void
    {
        ConfirmStockReservationJob::dispatch(orderId: $orderId)
            ->onQueue('stock');
    }

    public function dispatchReleaseReservation(int $orderId): void
    {
        ReleaseStockReservationJob::dispatch(orderId: $orderId)
            ->onQueue('stock');
    }
}
