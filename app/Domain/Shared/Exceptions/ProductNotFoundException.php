<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class ProductNotFoundException extends DomainException
{
    public static function withId(int $productId): self
    {
        return new self("Product with ID {$productId} not found");
    }
}
