<?php

declare(strict_types=1);

namespace App\Domain\User\Entity;

final readonly class User
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?\DateTimeImmutable $emailVerifiedAt = null,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
    }
}
