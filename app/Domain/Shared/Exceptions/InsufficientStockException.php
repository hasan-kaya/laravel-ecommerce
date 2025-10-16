<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class InsufficientStockException extends DomainException
{
    public static function forProduct(int $requested, int $available): self
    {
        return new self("Insufficient stock. Requested: {$requested}, Available: {$available}");
    }
}
