<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

use App\Application\Product\ProductData;
use App\Domain\Product\Repository\ProductRepositoryInterface;

final readonly class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function execute(CreateProductCommand $command): ProductData
    {
        $product = $this->productRepository->create(
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
