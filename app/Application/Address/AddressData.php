<?php

declare(strict_types=1);

namespace App\Application\Address;

use App\Domain\Address\Entity\Address;

final readonly class AddressData
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
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromDomain(Address $address): self
    {
        return new self(
            id: $address->id,
            userId: $address->userId,
            label: $address->label,
            fullName: $address->fullName,
            phone: $address->phone,
            city: $address->city,
            district: $address->district,
            neighborhood: $address->neighborhood,
            address: $address->address,
            type: $address->type,
            isDefault: $address->isDefault,
            createdAt: $address->createdAt?->format('Y-m-d H:i:s') ?? '',
            updatedAt: $address->updatedAt?->format('Y-m-d H:i:s') ?? '',
        );
    }
}
