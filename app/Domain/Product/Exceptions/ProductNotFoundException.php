<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ProductNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Product with ID '{$id}' not found.");
    }
}
