<?php

declare(strict_types=1);

namespace App\Infrastructure\Product\Repository;

use App\Domain\Product\Entity\Product as DomainProduct;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Infrastructure\Eloquent\Product as EloquentProduct;
use DateTimeImmutable;

final readonly class EloquentProductRepository implements ProductRepositoryInterface
{
    public function findById(int $id): ?DomainProduct
    {
        $product = EloquentProduct::find($id);

        return $product ? $this->toDomain($product) : null;
    }

    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $products = EloquentProduct::query()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $products->map(fn($product) => $this->toDomain($product))->all();
    }

    public function findByCategory(string $category, int $limit = 50, int $offset = 0): array
    {
        $products = EloquentProduct::where('category', $category)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $products->map(fn($product) => $this->toDomain($product))->all();
    }

    public function findByBrand(string $brand, int $limit = 50, int $offset = 0): array
    {
        $products = EloquentProduct::where('brand', $brand)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $products->map(fn($product) => $this->toDomain($product))->all();
    }

    public function create(
        string $name,
        ?string $description,
        string $category,
        string $brand,
        float $price,
        int $stock,
    ): DomainProduct {
        $eloquentProduct = EloquentProduct::create([
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'brand' => $brand,
            'price' => $price,
            'stock' => $stock,
        ]);

        return $this->toDomain($eloquentProduct);
    }

    public function update(
        int $id,
        string $name,
        ?string $description,
        string $category,
        string $brand,
        float $price,
        int $stock,
    ): DomainProduct {
        $eloquentProduct = EloquentProduct::findOrFail($id);

        $eloquentProduct->update([
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'brand' => $brand,
            'price' => $price,
            'stock' => $stock,
        ]);

        return $this->toDomain($eloquentProduct->fresh());
    }

    public function delete(int $id): bool
    {
        return EloquentProduct::destroy($id) > 0;
    }

    public function updateStock(int $id, int $stock): DomainProduct
    {
        $eloquentProduct = EloquentProduct::findOrFail($id);
        $eloquentProduct->update(['stock' => $stock]);

        return $this->toDomain($eloquentProduct->fresh());
    }

    public function search(
        ?string $query = null,
        ?string $category = null,
        ?string $brand = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        ?bool $inStock = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        $queryBuilder = EloquentProduct::query();

        // Full-text search on name and description
        if ($query !== null && $query !== '') {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            });
        }

        // Category filter
        if ($category !== null && $category !== '') {
            $queryBuilder->where('category', $category);
        }

        // Brand filter
        if ($brand !== null && $brand !== '') {
            $queryBuilder->where('brand', $brand);
        }

        // Price range filter
        if ($minPrice !== null) {
            $queryBuilder->where('price', '>=', $minPrice);
        }
        if ($maxPrice !== null) {
            $queryBuilder->where('price', '<=', $maxPrice);
        }

        // In stock filter
        if ($inStock !== null) {
            if ($inStock) {
                $queryBuilder->where('stock', '>', 0);
            } else {
                $queryBuilder->where('stock', '=', 0);
            }
        }

        $products = $queryBuilder
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $products->map(fn($product) => $this->toDomain($product))->all();
    }

    private function toDomain(EloquentProduct $eloquentProduct): DomainProduct
    {
        return new DomainProduct(
            id: $eloquentProduct->id,
            name: $eloquentProduct->name,
            description: $eloquentProduct->description,
            category: $eloquentProduct->category,
            brand: $eloquentProduct->brand,
            price: (float) $eloquentProduct->price,
            stock: $eloquentProduct->stock,
            createdAt: $eloquentProduct->created_at
                ? DateTimeImmutable::createFromMutable($eloquentProduct->created_at)
                : null,
            updatedAt: $eloquentProduct->updated_at
                ? DateTimeImmutable::createFromMutable($eloquentProduct->updated_at)
                : null,
        );
    }
}
