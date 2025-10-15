<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class AuthenticationException extends DomainException
{
    public static function invalidCredentials(): self
    {
        return new self('Invalid email or password', 401);
    }

    public static function userAlreadyExists(string $email): self
    {
        return new self("User with email {$email} already exists", 409);
    }
}
