<?php

declare(strict_types=1);

namespace App\Application\Order\GetMyOrders;

use App\Domain\Order\OrderRepositoryInterface;

final readonly class GetMyOrdersUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function execute(int $userId): array
    {
        return $this->orderRepository->findByUserId($userId);
    }
}
