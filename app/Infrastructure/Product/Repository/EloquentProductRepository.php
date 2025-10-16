<?php

declare(strict_types=1);

namespace App\Infrastructure\Product\Repository;

use App\Domain\Product\Entity\Product as DomainProduct;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\Repository\StockReservationRepositoryInterface;
use App\Domain\Shared\Search\SearchEngineInterface;
use App\Infrastructure\Eloquent\Product as EloquentProduct;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private StockReservationRepositoryInterface $stockReservationRepository,
        private SearchEngineInterface $elasticsearchClient,
    ) {
    }
    public function findById(int $id): ?DomainProduct
    {
        $product = EloquentProduct::find($id);

        return $product ? $this->toDomain($product) : null;
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

        // Index in Elasticsearch
        $this->indexProductInElasticsearch($eloquentProduct);

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

        // Re-index in Elasticsearch
        $this->indexProductInElasticsearch($eloquentProduct->fresh());

        return $this->toDomain($eloquentProduct->fresh());
    }

    public function delete(int $id): bool
    {
        $deleted = EloquentProduct::destroy($id) > 0;

        // Remove from Elasticsearch
        if ($deleted) {
            $this->elasticsearchClient->deleteDocument((string)$id);
        }

        return $deleted;
    }

    public function updateStock(int $id, int $stock): DomainProduct
    {
        $eloquentProduct = EloquentProduct::findOrFail($id);
        $eloquentProduct->update(['stock' => $stock]);

        // Re-index in Elasticsearch
        $this->indexProductInElasticsearch($eloquentProduct->fresh());

        return $this->toDomain($eloquentProduct->fresh());
    }

    public function findByIdWithLock(int $id): ?array
    {
        return DB::transaction(function () use ($id) {
            $product = EloquentProduct::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$product) {
                return null;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category,
                'brand' => $product->brand,
                'price' => (float) $product->price,
                'stock' => $product->stock,
            ];
        });
    }

    public function getAvailableStock(int $productId): int
    {
        $product = EloquentProduct::find($productId);
        if (!$product) {
            return 0;
        }

        $totalStock = $product->stock;
        $reservedStock = $this->stockReservationRepository->getTotalReservedQuantity($productId);

        return max(0, $totalStock - $reservedStock);
    }

    public function decrementStock(int $productId, int $quantity): void
    {
        EloquentProduct::where('id', $productId)
            ->decrement('stock', $quantity);
    }

    public function decrementStockOptimistic(int $productId, int $quantity): bool
    {
        $affectedRows = DB::table('products')
            ->where('id', $productId)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        return $affectedRows > 0;
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
        $startTime = microtime(true);
        
        // Build Elasticsearch query
        $elasticQuery = $this->buildElasticsearchQuery(
            $query,
            $category,
            $brand,
            $minPrice,
            $maxPrice,
            $inStock,
            $limit,
            $offset
        );

        // Search in Elasticsearch
        $elasticStart = microtime(true);
        $results = $this->elasticsearchClient->search($elasticQuery);
        $elasticTime = (microtime(true) - $elasticStart) * 1000;

        if (empty($results)) {
            Log::info("Search completed in {$elasticTime}ms (no results)");
            return [];
        }

        // Map Elasticsearch results directly to Domain objects (no DB query!)
        $products = array_map(function ($hit) {
            return new DomainProduct(
                id: (int) $hit['_id'],
                name: $hit['_source']['name'],
                description: $hit['_source']['description'] ?? null,
                category: $hit['_source']['category'],
                brand: $hit['_source']['brand'],
                price: (float) $hit['_source']['price'],
                stock: (int) $hit['_source']['stock']
            );
        }, $results);

        $totalTime = (microtime(true) - $startTime) * 1000;
        Log::info("Search timing (optimized)", [
            'elasticsearch' => round($elasticTime, 2) . 'ms',
            'total' => round($totalTime, 2) . 'ms',
            'results' => count($results),
        ]);

        return $products;
    }

    private function buildElasticsearchQuery(
        ?string $query,
        ?string $category,
        ?string $brand,
        ?float $minPrice,
        ?float $maxPrice,
        ?bool $inStock,
        int $limit,
        int $offset
    ): array {
        $must = [];
        $filter = [];

        // Full-text search on name and description
        if ($query !== null && $query !== '') {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['name^3', 'description'],
                    'type' => 'best_fields',
                    'operator' => 'or',
                ],
            ];
        }

        // Category filter (exact match)
        if ($category !== null && $category !== '') {
            $filter[] = ['term' => ['category' => $category]];
        }

        // Brand filter (exact match)
        if ($brand !== null && $brand !== '') {
            $filter[] = ['term' => ['brand' => $brand]];
        }

        // Price range filter
        if ($minPrice !== null || $maxPrice !== null) {
            $rangeQuery = [];
            if ($minPrice !== null) {
                $rangeQuery['gte'] = $minPrice;
            }
            if ($maxPrice !== null) {
                $rangeQuery['lte'] = $maxPrice;
            }
            $filter[] = ['range' => ['price' => $rangeQuery]];
        }

        // In stock filter
        if ($inStock !== null) {
            if ($inStock) {
                $filter[] = ['range' => ['stock' => ['gt' => 0]]];
            } else {
                $filter[] = ['term' => ['stock' => 0]];
            }
        }

        // Build final query
        $elasticQuery = [
            'from' => $offset,
            'size' => $limit,
            'query' => [
                'bool' => [],
            ],
        ];

        if (!empty($must)) {
            $elasticQuery['query']['bool']['must'] = $must;
        }

        if (!empty($filter)) {
            $elasticQuery['query']['bool']['filter'] = $filter;
        }

        // If no conditions, match all
        if (empty($must) && empty($filter)) {
            $elasticQuery['query'] = ['match_all' => (object)[]];
        }

        return $elasticQuery;
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

    private function indexProductInElasticsearch(EloquentProduct $product): void
    {
        $this->elasticsearchClient->indexDocument(
            (string)$product->id,
            [
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category,
                'brand' => $product->brand,
                'price' => (float)$product->price,
                'stock' => $product->stock,
                'created_at' => $product->created_at?->toIso8601String(),
            ]
        );
    }
}
