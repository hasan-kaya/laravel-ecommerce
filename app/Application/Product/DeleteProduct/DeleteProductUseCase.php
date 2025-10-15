<?php

declare(strict_types=1);

namespace App\Application\Product\DeleteProduct;

use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;

final readonly class DeleteProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function execute(int $id): bool
    {
        $product = $this->productRepository->findById($id);
        
        if ($product === null) {
            throw ProductNotFoundException::withId($id);
        }

        return $this->productRepository->delete($id);
    }
}
