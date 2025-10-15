<?php

declare(strict_types=1);

namespace App\Application\Product;

use App\Domain\Product\Entity\Product;

final readonly class ProductData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $category,
        public string $brand,
        public float $price,
        public int $stock,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromDomain(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            description: $product->description,
            category: $product->category,
            brand: $product->brand,
            price: $product->price,
            stock: $product->stock,
            createdAt: $product->createdAt?->format('Y-m-d H:i:s') ?? '',
            updatedAt: $product->updatedAt?->format('Y-m-d H:i:s') ?? '',
        );
    }
}
