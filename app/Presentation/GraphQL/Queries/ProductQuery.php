<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Queries;

use App\Application\Product\GetProduct\GetProductUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\ProductMapper;
use GraphQL\Error\Error;

final readonly class ProductQuery
{
    public function __construct(
        private GetProductUseCase $getProductUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($root, array $args): array
    {
        try {
            $id = (int) $args['id'];
            $product = $this->getProductUseCase->execute($id);

            return ProductMapper::toArray($product);
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to fetch product: ' . $e->getMessage());
        }
    }
}
