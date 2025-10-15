<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Domain\Auth\AccessToken;
use App\Domain\Auth\TokenServiceInterface;
use App\Domain\User\Entity\User as DomainUser;
use App\Infrastructure\Eloquent\User as EloquentUser;
use DateTimeImmutable;

final readonly class PassportTokenService implements TokenServiceInterface
{
    public function createToken(DomainUser $user, string $tokenName = 'auth_token'): AccessToken
    {
        $eloquentUser = EloquentUser::find($user->id);

        if (!$eloquentUser) {
            throw new \RuntimeException('User not found');
        }

        $tokenResult = $eloquentUser->createToken($tokenName);

        $expiresIn = (int) (config('passport.personal_access_tokens_expire_in') ?? 31536000);

        return new AccessToken(
            token: $tokenResult->accessToken,
            type: 'Bearer',
            expiresIn: $expiresIn,
            expiresAt: new DateTimeImmutable('+' . $expiresIn . ' seconds'),
        );
    }
}
