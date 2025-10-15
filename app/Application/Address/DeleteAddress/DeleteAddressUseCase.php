<?php

declare(strict_types=1);

namespace App\Application\Address\DeleteAddress;

use App\Domain\Address\Exceptions\AddressNotFoundException;
use App\Domain\Address\Repository\AddressRepositoryInterface;

final readonly class DeleteAddressUseCase
{
    public function __construct(
        private AddressRepositoryInterface $addressRepository,
    ) {
    }

    public function execute(int $id, int $userId): bool
    {
        // Verify address exists and belongs to user
        $address = $this->addressRepository->findById($id);
        
        if ($address === null) {
            throw AddressNotFoundException::withId($id);
        }

        if ($address->userId !== $userId) {
            throw AddressNotFoundException::unauthorized();
        }

        return $this->addressRepository->delete($id);
    }
}
