<?php

declare(strict_types=1);

namespace App\Infrastructure\User\Repository;

use App\Domain\User\Entity\User as DomainUser;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Eloquent\User as EloquentUser;
use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;

final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?DomainUser
    {
        $user = EloquentUser::find($id);

        return $user ? $this->toDomain($user) : null;
    }

    public function findByEmail(string $email): ?DomainUser
    {
        $user = EloquentUser::where('email', $email)->first();

        return $user ? $this->toDomain($user) : null;
    }

    public function verifyPassword(DomainUser $user, string $plainPassword): bool
    {
        $eloquentUser = EloquentUser::find($user->id);

        if (!$eloquentUser) {
            return false;
        }

        return Hash::check($plainPassword, $eloquentUser->password);
    }

    public function create(string $name, string $email, string $password): DomainUser
    {
        $eloquentUser = EloquentUser::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        return $this->toDomain($eloquentUser);
    }

    public function updateProfile(int $userId, string $name, string $email): DomainUser
    {
        $eloquentUser = EloquentUser::findOrFail($userId);
        
        $eloquentUser->update([
            'name' => $name,
            'email' => $email,
        ]);

        return $this->toDomain($eloquentUser->fresh());
    }

    public function emailExists(string $email): bool
    {
        return EloquentUser::where('email', $email)->exists();
    }

    private function toDomain(EloquentUser $eloquentUser): DomainUser
    {
        return new DomainUser(
            id: $eloquentUser->id,
            name: $eloquentUser->name,
            email: $eloquentUser->email,
            emailVerifiedAt: $eloquentUser->email_verified_at
                ? DateTimeImmutable::createFromMutable($eloquentUser->email_verified_at)
                : null,
            createdAt: $eloquentUser->created_at
                ? DateTimeImmutable::createFromMutable($eloquentUser->created_at)
                : null,
            updatedAt: $eloquentUser->updated_at
                ? DateTimeImmutable::createFromMutable($eloquentUser->updated_at)
                : null,
        );
    }
}
