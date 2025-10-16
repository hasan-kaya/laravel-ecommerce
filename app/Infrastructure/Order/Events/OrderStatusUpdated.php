<?php

declare(strict_types=1);

namespace App\Infrastructure\Order\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Order Status Updated Event
 * 
 * Broadcast when order status changes (for GraphQL subscriptions)
 * Frontend can subscribe to this event via WebSocket
 */
class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $status,
        public string $paymentStatus,
        public ?string $transactionId = null,
    ) {
    }

    /**
     * Get the channels the event should broadcast on
     */
    public function broadcastOn(): Channel
    {
        // Private channel for specific order
        return new Channel('orders.' . $this->orderId);
    }

    /**
     * The event's broadcast name
     */
    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'orderId' => $this->orderId,
            'status' => $this->status,
            'paymentStatus' => $this->paymentStatus,
            'transactionId' => $this->transactionId,
            'updatedAt' => now()->toISOString(),
        ];
    }
}
