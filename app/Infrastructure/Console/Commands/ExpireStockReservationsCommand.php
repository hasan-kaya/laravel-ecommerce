<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Commands;

use App\Domain\Product\Repository\StockReservationRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Expire Stock Reservations Command
 * 
 * Automatically expires PENDING reservations that have exceeded their expiration time
 * Runs periodically via Laravel Scheduler
 */
class ExpireStockReservationsCommand extends Command
{
    protected $signature = 'stock:expire-reservations';
    protected $description = 'Expire stock reservations that have exceeded their expiration time';

    public function __construct(
        private readonly StockReservationRepositoryInterface $reservationRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting stock reservation expiration...');

        try {
            // Get all expired PENDING reservations
            $expiredReservations = $this->reservationRepository->getExpiredReservations();

            if (empty($expiredReservations)) {
                $this->info('No expired reservations found.');
                return self::SUCCESS;
            }

            $count = 0;
            foreach ($expiredReservations as $reservation) {
                try {
                    $this->reservationRepository->expire($reservation['id']);
                    $count++;

                    Log::info('Stock reservation expired', [
                        'reservation_id' => $reservation['id'],
                        'order_id' => $reservation['order_id'],
                        'product_id' => $reservation['product_id'],
                        'quantity' => $reservation['quantity'],
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to expire reservation', [
                        'reservation_id' => $reservation['id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("Expired {$count} reservations.");
            Log::info('Stock reservation expiration completed', [
                'expired_count' => $count,
            ]);

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to expire reservations: ' . $e->getMessage());
            Log::error('Stock reservation expiration failed', [
                'error' => $e->getMessage(),
            ]);

            return self::FAILURE;
        }
    }
}
