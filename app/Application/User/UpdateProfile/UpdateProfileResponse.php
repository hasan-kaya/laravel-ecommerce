<?php

declare(strict_types=1);

namespace App\Application\User\UpdateProfile;

use App\Application\Auth\UserData;

final readonly class UpdateProfileResponse
{
    public function __construct(
        public UserData $user,
        public string $message,
    ) {
    }
}
