<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Queries;

use GraphQL\Error\Error;

final readonly class MeQuery
{
    /**
     * Get the authenticated user's profile
     *
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($root, array $args, $context): array
    {
        try {
            $user = $context->user();

            if (!$user) {
                throw new Error('Unauthenticated');
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at?->format('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            throw new Error($e->getMessage());
        }
    }
}
