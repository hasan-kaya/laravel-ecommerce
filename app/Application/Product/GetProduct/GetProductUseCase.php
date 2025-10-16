<?php

declare(strict_types=1);

namespace App\Application\Product\GetProduct;

use App\Application\Product\ProductData;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;

final readonly class GetProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * @param array<string> $fields
     */
    public function execute(int $id, array $fields = []): ProductData
    {
        $product = $this->productRepository->findById($id, $fields);
        
        if ($product === null) {
            throw ProductNotFoundException::withId($id);
        }

        return ProductData::fromDomain($product);
    }
}
