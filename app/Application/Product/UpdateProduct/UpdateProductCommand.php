<?php

declare(strict_types=1);

namespace App\Application\Product\UpdateProduct;

final readonly class UpdateProductCommand
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $category,
        public string $brand,
        public float $price,
        public int $stock,
    ) {
    }
}
