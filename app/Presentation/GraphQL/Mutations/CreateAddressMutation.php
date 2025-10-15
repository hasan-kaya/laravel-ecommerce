<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Address\CreateAddress\CreateAddressCommand;
use App\Application\Address\CreateAddress\CreateAddressUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\AddressMapper;
use GraphQL\Error\Error;

final readonly class CreateAddressMutation
{
    public function __construct(
        private CreateAddressUseCase $createAddressUseCase,
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
            $input = $args['input'];

            $command = new CreateAddressCommand(
                userId: $userId,
                label: $input['label'],
                fullName: $input['full_name'],
                phone: $input['phone'],
                city: $input['city'],
                district: $input['district'],
                neighborhood: $input['neighborhood'],
                address: $input['address'],
                type: $input['type'],
                isDefault: $input['is_default'] ?? false,
            );

            $addressData = $this->createAddressUseCase->execute($command);

            return AddressMapper::toArray($addressData);
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to create address: ' . $e->getMessage());
        }
    }
}
