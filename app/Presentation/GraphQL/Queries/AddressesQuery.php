<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Queries;

use App\Application\Address\ListAddresses\ListAddressesUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\AddressMapper;
use GraphQL\Error\Error;

final readonly class AddressesQuery
{
    public function __construct(
        private ListAddressesUseCase $listAddressesUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke($root, array $args, $context): array
    {
        try {
            $userId = $context->user()->id;
            $addresses = $this->listAddressesUseCase->execute($userId);

            return array_map(
                fn($address) => AddressMapper::toArray($address),
                $addresses
            );
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to fetch addresses: ' . $e->getMessage());
        }
    }
}
