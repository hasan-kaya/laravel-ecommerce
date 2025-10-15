<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Address\UpdateAddress\UpdateAddressCommand;
use App\Application\Address\UpdateAddress\UpdateAddressUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\AddressMapper;
use GraphQL\Error\Error;

final readonly class UpdateAddressMutation
{
    public function __construct(
        private UpdateAddressUseCase $updateAddressUseCase,
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

            $command = new UpdateAddressCommand(
                id: (int) $args['id'],
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

            $addressData = $this->updateAddressUseCase->execute($command);

            return AddressMapper::toArray($addressData);
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to update address: ' . $e->getMessage());
        }
    }
}
