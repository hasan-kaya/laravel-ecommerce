<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mappers;

use App\Application\Product\ProductData;

final class ProductMapper
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(ProductData $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'category' => $product->category,
            'brand' => $product->brand,
            'price' => $product->price,
            'stock' => $product->stock,
            'created_at' => $product->createdAt,
            'updated_at' => $product->updatedAt,
        ];
    }
}
