<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Product\UpdateProduct\UpdateProductCommand;
use App\Application\Product\UpdateProduct\UpdateProductUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\ProductMapper;
use GraphQL\Error\Error;

final readonly class UpdateProductMutation
{
    public function __construct(
        private UpdateProductUseCase $updateProductUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($root, array $args): array
    {
        try {
            $input = $args['input'];

            $command = new UpdateProductCommand(
                id: (int) $args['id'],
                name: $input['name'],
                description: $input['description'] ?? null,
                category: $input['category'],
                brand: $input['brand'],
                price: (float) $input['price'],
                stock: (int) $input['stock'],
            );

            $productData = $this->updateProductUseCase->execute($command);

            return ProductMapper::toArray($productData);
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to update product: ' . $e->getMessage());
        }
    }
}
