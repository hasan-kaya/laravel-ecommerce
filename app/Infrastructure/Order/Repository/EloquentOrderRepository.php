<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Repository;

use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Infrastructure\Eloquent\Order;
use App\Infrastructure\Eloquent\OrderItem;
use Illuminate\Support\Facades\DB;

final readonly class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): int
    {
        $order = Order::create($data);
        return $order->id;
    }

    public function update(int $orderId, array $data): bool
    {
        return Order::where('id', $orderId)->update($data) > 0;
    }

    public function createOrderItems(int $orderId, array $items): void
    {
        foreach ($items as $item) {
            OrderItem::create([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'line_total' => $item['line_total'],
            ]);
        }
    }

    public function findById(int $id): ?array
    {
        $order = Order::with(['items', 'user'])->find($id);
        
        if (!$order) {
            return null;
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $order->user_id,
            'status' => $order->status->value,
            'payment_status' => $order->payment_status->value,
            'total_amount' => (float) $order->total_amount,
            'created_at' => $order->created_at->toIso8601String(),
            'items' => $order->items->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'line_total' => (float) $item->line_total,
            ])->toArray(),
        ];
    }

    public function findByUserId(int $userId): array
    {
        return Order::where('user_id', $userId)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'payment_status' => $order->payment_status->value,
                'total_amount' => (float) $order->total_amount,
                'created_at' => $order->created_at->toIso8601String(),
                'items_count' => $order->items->count(),
            ])
            ->toArray();
    }
}
