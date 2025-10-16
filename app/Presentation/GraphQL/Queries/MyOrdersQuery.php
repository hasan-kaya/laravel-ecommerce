<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Queries;

use App\Application\Order\GetMyOrders\GetMyOrdersUseCase;

final readonly class MyOrdersQuery
{
    public function __construct(
        private GetMyOrdersUseCase $getMyOrdersUseCase,
    ) {
    }

    public function __invoke($root, array $args, $context): array
    {
        $user = $context->user();
        return $this->getMyOrdersUseCase->execute($user->id);
    }
}
