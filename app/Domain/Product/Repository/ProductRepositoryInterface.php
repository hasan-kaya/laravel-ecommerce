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
     * Find product with pessimistic lock (for race condition protection)
     * Returns array format for consistency
     */
    public function findByIdWithLock(int $id): ?array;

    /**
     * Get available stock (total stock - PENDING reservations)
     * Only PENDING reservations are counted, as CONFIRMED already decremented
     */
    public function getAvailableStock(int $productId): int;

    /**
     * Atomically decrement stock (pessimistic locking)
     * Throws exception if insufficient stock
     */
    public function decrementStock(int $productId, int $quantity): void;

    /**
     * Optimistically decrement stock without lock
     * Returns true if successful, false if insufficient stock
     * Uses WHERE stock >= quantity for race condition safety
     */
    public function decrementStockOptimistic(int $productId, int $quantity): bool;

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
