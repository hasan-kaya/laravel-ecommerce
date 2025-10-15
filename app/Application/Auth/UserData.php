<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\User\Entity\User;

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

    public static function fromDomain(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            createdAt: $user->createdAt?->format('Y-m-d H:i:s') ?? '',
            updatedAt: $user->updatedAt?->format('Y-m-d H:i:s') ?? '',
        );
    }
}
