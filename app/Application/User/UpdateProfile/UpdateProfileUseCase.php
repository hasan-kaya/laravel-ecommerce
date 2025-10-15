<?php

declare(strict_types=1);

namespace App\Application\User\UpdateProfile;

use App\Application\Auth\UserData;
use App\Domain\Shared\Validation\EmailValidator;
use App\Domain\User\Exceptions\ProfileUpdateException;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class UpdateProfileUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(UpdateProfileCommand $command): UpdateProfileResponse
    {
        // Validate email format
        EmailValidator::validate($command->email);

        // Get current user
        $user = $this->userRepository->findById($command->userId);
        
        if ($user === null) {
            throw ProfileUpdateException::cannotUpdateOwnProfile();
        }

        // Check if email is taken by another user
        if ($user->email !== $command->email) {
            $existingUser = $this->userRepository->findByEmail($command->email);
            if ($existingUser !== null && $existingUser->id !== $user->id) {
                throw ProfileUpdateException::emailAlreadyTaken($command->email);
            }
        }

        // Update profile
        $updatedUser = $this->userRepository->updateProfile(
            userId: $command->userId,
            name: $command->name,
            email: $command->email,
        );

        return new UpdateProfileResponse(
            user: UserData::fromDomain($updatedUser),
            message: 'Profile updated successfully.',
        );
    }
}
