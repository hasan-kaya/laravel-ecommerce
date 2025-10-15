<?php

declare(strict_types=1);

namespace App\Domain\Address\Repository;

use App\Domain\Address\Entity\Address;

interface AddressRepositoryInterface
{
    public function findById(int $id): ?Address;

    /**
     * @return Address[]
     */
    public function findByUserId(int $userId): array;

    public function create(
        int $userId,
        string $label,
        string $fullName,
        string $phone,
        string $city,
        string $district,
        string $neighborhood,
        string $address,
        string $type,
        bool $isDefault,
    ): Address;

    public function update(
        int $id,
        string $label,
        string $fullName,
        string $phone,
        string $city,
        string $district,
        string $neighborhood,
        string $address,
        string $type,
        bool $isDefault,
    ): Address;

    public function delete(int $id): bool;

    public function setAsDefault(int $id, int $userId): void;

    public function findDefaultByUserIdAndType(int $userId, string $type): ?Address;
}
