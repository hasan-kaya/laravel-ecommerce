<?php

declare(strict_types=1);

namespace App\Domain\Address\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AddressNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Address with ID '{$id}' not found.");
    }

    public static function unauthorized(): self
    {
        return new self('You are not authorized to access this address.');
    }
}
