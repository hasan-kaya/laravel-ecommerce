<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Product\CreateProduct\CreateProductCommand;
use App\Application\Product\CreateProduct\CreateProductUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\ProductMapper;
use GraphQL\Error\Error;

final readonly class CreateProductMutation
{
    public function __construct(
        private CreateProductUseCase $createProductUseCase,
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

            $command = new CreateProductCommand(
                name: $input['name'],
                description: $input['description'] ?? null,
                category: $input['category'],
                brand: $input['brand'],
                price: (float) $input['price'],
                stock: (int) $input['stock'],
            );

            $productData = $this->createProductUseCase->execute($command);

            return ProductMapper::toArray($productData);
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to create product: ' . $e->getMessage());
        }
    }
}
