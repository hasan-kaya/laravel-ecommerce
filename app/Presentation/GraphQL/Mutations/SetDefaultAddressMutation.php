<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Address\SetDefaultAddress\SetDefaultAddressUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\AddressMapper;
use GraphQL\Error\Error;

final readonly class SetDefaultAddressMutation
{
    public function __construct(
        private SetDefaultAddressUseCase $setDefaultAddressUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($root, array $args, $context): array
    {
        try {
            $userId = $context->user()->id;
            $id = (int) $args['id'];

            $addressData = $this->setDefaultAddressUseCase->execute($id, $userId);

            return AddressMapper::toArray($addressData);
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to set default address: ' . $e->getMessage());
        }
    }
}
