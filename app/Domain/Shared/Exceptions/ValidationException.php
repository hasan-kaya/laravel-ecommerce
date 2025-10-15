<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

final class ValidationException extends DomainException
{
    public static function invalidEmail(string $email): self
    {
        return new self("Invalid email format: {$email}", 400);
    }

    public static function weakPassword(): self
    {
        return new self('Password must be at least 8 characters', 400);
    }
}
