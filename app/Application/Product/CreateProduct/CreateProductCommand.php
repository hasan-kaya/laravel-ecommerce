<?php

declare(strict_types=1);

namespace App\Application\Product\CreateProduct;

final readonly class CreateProductCommand
{
    public function __construct(
        public string $name,
        public ?string $description,
        public string $category,
        public string $brand,
        public float $price,
        public int $stock,
    ) {
    }
}
