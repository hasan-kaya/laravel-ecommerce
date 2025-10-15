<?php

declare(strict_types=1);

namespace App\Domain\Shared\Validation;

use App\Domain\Shared\Exceptions\ValidationException;

final class EmailValidator
{
    public static function validate(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidEmail($email);
        }
    }
}
