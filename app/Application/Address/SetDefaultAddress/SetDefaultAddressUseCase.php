<?php

declare(strict_types=1);

namespace App\Application\Address\SetDefaultAddress;

use App\Application\Address\AddressData;
use App\Domain\Address\Exceptions\AddressNotFoundException;
use App\Domain\Address\Repository\AddressRepositoryInterface;

final readonly class SetDefaultAddressUseCase
{
    public function __construct(
        private AddressRepositoryInterface $addressRepository,
    ) {
    }

    public function execute(int $id, int $userId): AddressData
    {
        // Verify address exists and belongs to user
        $address = $this->addressRepository->findById($id);
        
        if ($address === null) {
            throw AddressNotFoundException::withId($id);
        }

        if ($address->userId !== $userId) {
            throw AddressNotFoundException::unauthorized();
        }

        // Set as default
        $this->addressRepository->setAsDefault($id, $userId);

        // Return updated address
        $updatedAddress = $this->addressRepository->findById($id);

        return AddressData::fromDomain($updatedAddress);
    }
}
