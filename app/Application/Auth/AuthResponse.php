<?php

declare(strict_types=1);

namespace App\Application\Auth;

final readonly class AuthResponse
{
    public function __construct(
        public string $accessToken,
        public string $tokenType,
        public int $expiresIn,
        public UserData $user,
    ) {
    }
}
