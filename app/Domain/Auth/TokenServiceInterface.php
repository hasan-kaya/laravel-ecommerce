<?php

declare(strict_types=1);

namespace App\Domain\Auth;

use App\Domain\User\Entity\User;

interface TokenServiceInterface
{
    public function createToken(User $user, string $tokenName = 'auth_token'): AccessToken;
}
