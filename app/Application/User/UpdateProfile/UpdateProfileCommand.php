<?php

declare(strict_types=1);

namespace App\Application\User\UpdateProfile;

final readonly class UpdateProfileCommand
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $email,
    ) {
    }
}
