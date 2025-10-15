<?php

declare(strict_types=1);

namespace App\Application\Auth;

final readonly class UserData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
