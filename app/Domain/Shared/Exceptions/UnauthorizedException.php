<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class UnauthorizedException extends DomainException
{
    public static function adminOnly(): self
    {
        return new self('This action requires admin privileges.');
    }
}
