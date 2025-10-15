<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

use App\Application\Product\ProductData;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;

final readonly class UpdateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function execute(UpdateProductCommand $command): ProductData
    {
        $existingProduct = $this->productRepository->findById($command->id);
        
        if ($existingProduct === null) {
            throw ProductNotFoundException::withId($command->id);
        }

        $product = $this->productRepository->update(
            id: $command->id,
            name: $command->name,
            description: $command->description,
            category: $command->category,
            brand: $command->brand,
            price: $command->price,
            stock: $command->stock,
        );

        return ProductData::fromDomain($product);
    }
}
