<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\Product\Repository\StockReservationRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Release Stock Reservation Job
 * 
 * Releases stock reservation (payment failed or expired)
 */
class ReleaseStockReservationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [5, 15, 30];
    public int $timeout = 60;

    public function __construct(
        public int $orderId,
    ) {
        $this->onQueue('stock');
    }

    public function handle(
        StockReservationRepositoryInterface $reservationRepository,
    ): void {
        Log::info('Releasing stock reservation', [
            'order_id' => $this->orderId,
        ]);

        try {
            // Find reservation
            $reservation = $reservationRepository->findByOrderId($this->orderId);

            if (!$reservation) {
                Log::warning('Reservation not found', [
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            if ($reservation['status'] !== 'pending') {
                Log::warning('Reservation already processed', [
                    'order_id' => $this->orderId,
                    'status' => $reservation['status'],
                ]);
                return;
            }

            // Release reservation (stock becomes available again)
            $reservationRepository->release($reservation['id']);

            Log::info('Stock reservation released', [
                'order_id' => $this->orderId,
                'product_id' => $reservation['product_id'],
                'quantity' => $reservation['quantity'],
            ]);
        } catch (\Exception $e) {
            Log::error('Stock release failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Stock release job permanently failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
