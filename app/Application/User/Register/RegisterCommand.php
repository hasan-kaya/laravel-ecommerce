<?php

declare(strict_types=1);

namespace App\Application\User\Register;

final readonly class RegisterCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {
    }
}
