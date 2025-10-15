<?php

declare(strict_types=1);

namespace App\Application\Address\UpdateAddress;

final readonly class UpdateAddressCommand
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $label,
        public string $fullName,
        public string $phone,
        public string $city,
        public string $district,
        public string $neighborhood,
        public string $address,
        public string $type,
        public bool $isDefault,
    ) {
    }
}
