<?php

declare(strict_types=1);

namespace App\Domain\Order;

interface OrderRepositoryInterface
{
    public function create(array $data): int;
    
    public function update(int $orderId, array $data): bool;
    
    public function createOrderItems(int $orderId, array $items): void;
    
    public function findById(int $id): ?array;
    
    public function findByUserId(int $userId): array;
}
