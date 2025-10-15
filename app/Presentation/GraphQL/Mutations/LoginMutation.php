<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\User\Login\LoginCommand;
use App\Application\User\Login\LoginUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use GraphQL\Error\Error;

final readonly class LoginMutation
{
    public function __construct(
        private LoginUseCase $loginUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($root, array $args): array
    {
        try {
            $command = new LoginCommand(
                email: $args['input']['email'],
                password: $args['input']['password'],
            );

            $response = $this->loginUseCase->execute($command);

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
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Login failed');
        }
    }
}
