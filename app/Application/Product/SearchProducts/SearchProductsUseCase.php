<?php

declare(strict_types=1);

namespace App\Application\Product\SearchProducts;

use App\Application\Product\ProductData;
use App\Domain\Product\Repository\ProductRepositoryInterface;

final readonly class SearchProductsUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * @return ProductData[]
     */
    public function execute(
        ?string $query = null,
        ?string $category = null,
        ?string $brand = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?bool $inStock = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $products = $this->productRepository->search(
            query: $query,
            category: $category,
            brand: $brand,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            inStock: $inStock,
            limit: $limit,
            offset: $offset
        );

        return array_map(
            fn($product) => ProductData::fromDomain($product),
            $products
        );
    }
}
