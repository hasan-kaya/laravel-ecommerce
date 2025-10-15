<?php

declare(strict_types=1);

namespace App\Domain\Product\Entity;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public string $category,
        public string $brand,
        public float $price,
        public int $stock,
        public ?\DateTimeImmutable $createdAt = null,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
    }
}
