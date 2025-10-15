<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Product\DeleteProduct\DeleteProductUseCase;
use App\Domain\Shared\Exceptions\DomainException;
use GraphQL\Error\Error;

final readonly class DeleteProductMutation
{
    public function __construct(
        private DeleteProductUseCase $deleteProductUseCase,
    ) {
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function __invoke($root, array $args): array
    {
        try {
            $id = (int) $args['id'];
            $success = $this->deleteProductUseCase->execute($id);

            return [
                'success' => $success,
                'message' => $success ? 'Product deleted successfully' : 'Failed to delete product',
            ];
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to delete product: ' . $e->getMessage());
        }
    }
}
