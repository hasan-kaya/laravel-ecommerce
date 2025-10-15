<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\User\UpdateProfile\UpdateProfileCommand;
use App\Application\User\UpdateProfile\UpdateProfileUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use GraphQL\Error\Error;

final readonly class UpdateProfileMutation
{
    public function __construct(
        private UpdateProfileUseCase $updateProfileUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($root, array $args, $context): array
    {
        try {
            $userId = $context->user()->id;

            $command = new UpdateProfileCommand(
                userId: $userId,
                name: $args['input']['name'],
                email: $args['input']['email'],
            );

            $response = $this->updateProfileUseCase->execute($command);

            return [
                'user' => [
                    'id' => $response->user->id,
                    'name' => $response->user->name,
                    'email' => $response->user->email,
                    'role' => $response->user->role,
                    'created_at' => $response->user->createdAt,
                    'updated_at' => $response->user->updatedAt,
                ],
                'message' => $response->message,
            ];
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Profile update failed');
        }
    }
}
