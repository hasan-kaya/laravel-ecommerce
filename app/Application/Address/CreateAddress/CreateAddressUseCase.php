<?php

declare(strict_types=1);

namespace App\Application\Address\CreateAddress;

use App\Application\Address\AddressData;
use App\Domain\Address\Repository\AddressRepositoryInterface;

final readonly class CreateAddressUseCase
{
    public function __construct(
        private AddressRepositoryInterface $addressRepository,
    ) {
    }

    public function execute(CreateAddressCommand $command): AddressData
    {
        $address = $this->addressRepository->create(
            userId: $command->userId,
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
