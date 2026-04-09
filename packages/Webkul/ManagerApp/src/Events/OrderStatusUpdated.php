<?php

namespace Webkul\ManagerApp\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Webkul\ManagerApp\Http\Resources\OrderResource;
use Webkul\Sales\Models\Order;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(public readonly Order $order) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * Each inventory source gets its own private channel so only managers
     * assigned to that warehouse receive the update.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->order->inventory_source_id === null) {
            return [];
        }

        return [
            new PrivateChannel('manager.warehouse.'.$this->order->inventory_source_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return (new OrderResource($this->order))->resolve();
    }
}
