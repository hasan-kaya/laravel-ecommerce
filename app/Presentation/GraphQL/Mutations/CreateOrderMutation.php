<?php

declare(strict_types=1);

namespace App\Presentation\GraphQL\Mutations;

use App\Application\Order\CreateOrder\CreateOrderCommand;
use App\Application\Order\CreateOrder\CreateOrderUseCase;
use App\Domain\Payment\Enums\PaymentMethod;
use App\Domain\Shared\Exceptions\DomainException;
use GraphQL\Error\Error;

final readonly class CreateOrderMutation
{
    public function __construct(
        private CreateOrderUseCase $createOrderUseCase,
    ) {
    }

    public function __invoke($root, array $args, $context): array
    {
        try {
            $user = $context->user();
            $input = $args['input'];

            $command = new CreateOrderCommand(
                userId: $user->id,
                productId: (int) $input['product_id'],
                quantity: (int) $input['quantity'],
                paymentMethod: PaymentMethod::from($input['payment_method']),
            );

            $order = $this->createOrderUseCase->execute($command);

            return [
                'id' => $order->id,
                'order_number' => $order->orderNumber,
                'status' => $order->status,
                'payment_status' => $order->paymentStatus,
                'total_amount' => $order->totalAmount,
                'items' => $order->items,
                'created_at' => $order->createdAt,
            ];
        } catch (DomainException $e) {
            throw new Error($e->getMessage());
        } catch (\Throwable $e) {
            throw new Error('Failed to create order: ' . $e->getMessage());
        }
    }
}
