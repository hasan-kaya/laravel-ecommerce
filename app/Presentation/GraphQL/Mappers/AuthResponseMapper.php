<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mappers;

use App\Application\Auth\AuthResponse;

final class AuthResponseMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(AuthResponse $response): array
    {
        return [
            'access_token' => $response->accessToken,
            'token_type' => $response->tokenType,
            'expires_in' => $response->expiresIn,
            'user' => [
                'id' => $response->user->id,
                'name' => $response->user->name,
                'email' => $response->user->email,
                'created_at' => $response->user->createdAt,
                'updated_at' => $response->user->updatedAt,
            ],
        ];
    }
}
