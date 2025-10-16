<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Queries;

use App\Application\Product\SearchProducts\SearchProductsUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use App\Presentation\GraphQL\Concerns\ExtractsRequestedFields;
use App\Presentation\GraphQL\Mappers\ProductMapper;
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ResolveInfo;

final readonly class ProductsQuery
{
    use ExtractsRequestedFields;

    public function __construct(
        private SearchProductsUseCase $searchProductsUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke($root, array $args, $context, ResolveInfo $resolveInfo): array
    {
        try {
            $filter = $args['filter'] ?? [];
            $limit = $args['limit'] ?? 50;
            $offset = $args['offset'] ?? 0;

            $products = $this->searchProductsUseCase->execute(
                query: $filter['query'] ?? null,
                category: $filter['category'] ?? null,
                brand: $filter['brand'] ?? null,
                minPrice: isset($filter['minPrice']) ? (float) $filter['minPrice'] : null,
                maxPrice: isset($filter['maxPrice']) ? (float) $filter['maxPrice'] : null,
                inStock: $filter['inStock'] ?? null,
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
