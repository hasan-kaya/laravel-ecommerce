<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Repository\StockReservationRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Confirm Stock Reservation Job
 * 
 * Confirms reservation and decrements actual stock (payment successful)
 */
class ConfirmStockReservationJob implements ShouldQueue
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
        ProductRepositoryInterface $productRepository,
    ): void {
        Log::info('Confirming stock reservation', [
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

            // Decrement actual stock
            $productRepository->decrementStock(
                $reservation['product_id'],
                $reservation['quantity']
            );

            // Confirm reservation
            $reservationRepository->confirm($reservation['id']);

            Log::info('Stock reservation confirmed and stock decremented', [
                'order_id' => $this->orderId,
                'product_id' => $reservation['product_id'],
                'quantity' => $reservation['quantity'],
            ]);
        } catch (\Exception $e) {
            Log::error('Stock confirmation failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Stock confirmation job permanently failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
