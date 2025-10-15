<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Address\DeleteAddress\DeleteAddressUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use GraphQL\Error\Error;

final readonly class DeleteAddressMutation
{
    public function __construct(
        private DeleteAddressUseCase $deleteAddressUseCase,
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

            $success = $this->deleteAddressUseCase->execute($id, $userId);

            return [
                'success' => $success,
                'message' => $success ? 'Address deleted successfully' : 'Failed to delete address',
            ];
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to delete address: ' . $e->getMessage());
        }
    }
}
