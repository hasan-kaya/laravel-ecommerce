<?php

declare(strict_types=1);

namespace App\Domain\Product\Repository;

use App\Domain\Product\Entity\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    /**
     * @return Product[]
     */
    public function findAll(int $limit = 50, int $offset = 0): array;

    /**
     * @return Product[]
     */
    public function findByCategory(string $category, int $limit = 50, int $offset = 0): array;

    /**
     * @return Product[]
     */
    public function findByBrand(string $brand, int $limit = 50, int $offset = 0): array;

    public function create(
        string $name,
        ?string $description,
        string $category,
        string $brand,
        float $price,
        int $stock,
    ): Product;

    public function update(
        int $id,
        string $name,
        ?string $description,
        string $category,
        string $brand,
        float $price,
        int $stock,
    ): Product;

    public function delete(int $id): bool;

    public function updateStock(int $id, int $stock): Product;

    /**
     * @return Product[]
     */
    public function search(
        ?string $query = null,
        ?string $category = null,
        ?string $brand = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?bool $inStock = null,
        int $limit = 50,
        int $offset = 0
    ): array;
}
