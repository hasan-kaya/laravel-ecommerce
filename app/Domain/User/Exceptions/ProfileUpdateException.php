<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class ProfileUpdateException extends DomainException
{
    public static function emailAlreadyTaken(string $email): self
    {
        return new self("Email '{$email}' is already taken by another user.");
    }

    public static function cannotUpdateOwnProfile(): self
    {
        return new self('You can only update your own profile.');
    }
}
