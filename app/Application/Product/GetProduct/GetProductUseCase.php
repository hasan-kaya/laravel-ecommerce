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

    public function execute(int $id): ProductData
    {
        $product = $this->productRepository->findById($id);
        
        if ($product === null) {
            throw ProductNotFoundException::withId($id);
        }

        return ProductData::fromDomain($product);
    }
}
