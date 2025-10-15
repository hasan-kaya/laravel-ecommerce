<?php

declare(strict_types=1);

namespace App\Application\Address\UpdateAddress;

use App\Application\Address\AddressData;
use App\Domain\Address\Exceptions\AddressNotFoundException;
use App\Domain\Address\Repository\AddressRepositoryInterface;

final readonly class UpdateAddressUseCase
{
    public function __construct(
        private AddressRepositoryInterface $addressRepository,
    ) {
    }

    public function execute(UpdateAddressCommand $command): AddressData
    {
        // Verify address exists and belongs to user
        $existingAddress = $this->addressRepository->findById($command->id);
        
        if ($existingAddress === null) {
            throw AddressNotFoundException::withId($command->id);
        }

        if ($existingAddress->userId !== $command->userId) {
            throw AddressNotFoundException::unauthorized();
        }

        $address = $this->addressRepository->update(
            id: $command->id,
            label: $command->label,
            fullName: $command->fullName,
            phone: $command->phone,
            city: $command->city,
            district: $command->district,
            neighborhood: $command->neighborhood,
            address: $command->address,
            type: $command->type,
            isDefault: $command->isDefault,
        );

        return AddressData::fromDomain($address);
    }
}
