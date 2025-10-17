<?php

declare(strict_types=1);

namespace App\Domain\Order\Contract;

interface StockReservationJobDispatcherInterface
{
    public function dispatchConfirmReservation(int $orderId): void;

    public function dispatchReleaseReservation(int $orderId): void;
}
