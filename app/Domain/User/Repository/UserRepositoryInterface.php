<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\Entity\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function verifyPassword(User $user, string $plainPassword): bool;

    public function create(string $name, string $email, string $password): User;

    public function updateProfile(int $userId, string $name, string $email): User;

    public function emailExists(string $email): bool;
}
