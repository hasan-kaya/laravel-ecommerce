<?php

declare(strict_types=1);

namespace App\Domain\Product\Enums;

enum ReservationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case RELEASED = 'released';
    case EXPIRED = 'expired';
}
