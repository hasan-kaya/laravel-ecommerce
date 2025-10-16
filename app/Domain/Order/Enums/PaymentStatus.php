<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
}
