<?php

declare(strict_types=1);

namespace App\Application\Address\ListAddresses;

use App\Application\Address\AddressData;
use App\Domain\Address\Repository\AddressRepositoryInterface;

final readonly class ListAddressesUseCase
{
    public function __construct(
        private AddressRepositoryInterface $addressRepository,
    ) {
    }

    /**
     * @return AddressData[]
     */
    public function execute(int $userId): array
    {
        $addresses = $this->addressRepository->findByUserId($userId);

        return array_map(
            fn($address) => AddressData::fromDomain($address),
            $addresses
        );
    }
}
