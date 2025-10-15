<?php

declare(strict_types=1);

namespace App\Application\User\Login;

use App\Application\Auth\AuthResponse;
use App\Application\Auth\UserData;
use App\Domain\Auth\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Domain\Shared\Validation\EmailValidator;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class LoginUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenServiceInterface $tokenService,
    ) {
    }

    public function execute(LoginCommand $command): AuthResponse
    {
        EmailValidator::validate($command->email);

        $user = $this->userRepository->findByEmail($command->email);

        if ($user === null) {
            throw AuthenticationException::invalidCredentials();
        }

        if (!$this->userRepository->verifyPassword($user, $command->password)) {
            throw AuthenticationException::invalidCredentials();
        }

        $accessToken = $this->tokenService->createToken($user);

        return new AuthResponse(
            accessToken: $accessToken->token,
            tokenType: $accessToken->type,
            expiresIn: $accessToken->expiresIn,
            user: UserData::fromDomain($user),
        );
    }
}
