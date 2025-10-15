<?php

declare(strict_types=1);

namespace App\Application\User\Register;

use App\Application\Auth\AuthResponse;
use App\Application\Auth\UserData;
use App\Domain\Auth\TokenServiceInterface;
use App\Domain\Auth\Exceptions\AuthenticationException;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Validation\EmailValidator;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class RegisterUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenServiceInterface $tokenService,
    ) {
    }

    public function execute(RegisterCommand $command): AuthResponse
    {
        EmailValidator::validate($command->email);

        if (strlen($command->password) < 8) {
            throw ValidationException::weakPassword();
        }

        if ($this->userRepository->emailExists($command->email)) {
            throw AuthenticationException::userAlreadyExists($command->email);
        }

        $user = $this->userRepository->create(
            name: $command->name,
            email: $command->email,
            password: $command->password,
        );

        $accessToken = $this->tokenService->createToken($user);

        return new AuthResponse(
            accessToken: $accessToken->token,
            tokenType: $accessToken->type,
            expiresIn: $accessToken->expiresIn,
            user: UserData::fromDomain($user),
        );
    }
}
