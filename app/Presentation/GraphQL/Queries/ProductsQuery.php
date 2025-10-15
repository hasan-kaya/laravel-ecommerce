<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Queries;

use App\Application\Product\SearchProducts\SearchProductsUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Mappers\ProductMapper;
use GraphQL\Error\Error;

final readonly class ProductsQuery
{
    public function __construct(
        private SearchProductsUseCase $searchProductsUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke($root, array $args): array
    {
        try {
            $filter = $args['filter'] ?? [];
            $limit = $args['limit'] ?? 50;
            $offset = $args['offset'] ?? 0;

            $products = $this->searchProductsUseCase->execute(
                query: $filter['search'] ?? null,
                category: $filter['category'] ?? null,
                brand: $filter['brand'] ?? null,
                minPrice: isset($filter['min_price']) ? (float) $filter['min_price'] : null,
                maxPrice: isset($filter['max_price']) ? (float) $filter['max_price'] : null,
                inStock: $filter['in_stock'] ?? null,
                limit: $limit,
                offset: $offset
            );

            return array_map(
                fn($product) => ProductMapper::toArray($product),
                $products
            );
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to fetch products: ' . $e->getMessage());
        }
    }
}
