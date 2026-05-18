<?php

namespace Webkul\Sales\Observers;

use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderStatusHistory;

class OrderObserver
{
    /**
     * Record an initial history entry when an order is created.
     */
    public function created(Order $order): void
    {
        if (! $order->status) {
            return;
        }

        OrderStatusHistory::create([
            'order_id'   => $order->id,
            'old_status' => null,
            'new_status' => $order->status,
            'source'     => 'system',
        ]);
    }

    /**
     * Record a history entry when the order status changes.
     *
     * The OrderStatusTransitionService already writes history inside its transaction,
     * so the observer guards against direct `save()` calls outside the service
     * (e.g., system auto-updates from OrderRepository::updateOrderStatus).
     */
    public function updating(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        // Skip if a history entry with this exact transition already exists for this
        // request cycle (the service writes one before save(); the observer would add
        // a duplicate).  We detect this by checking if the latest history row already
        // reflects the pending change.
        $latestHistory = OrderStatusHistory::where('order_id', $order->id)
            ->latest('created_at')
            ->first();

        if (
            $latestHistory
            && $latestHistory->old_status === $order->getOriginal('status')
            && $latestHistory->new_status === $order->status
        ) {
            return;
        }

        OrderStatusHistory::create([
            'order_id'   => $order->id,
            'old_status' => $order->getOriginal('status'),
            'new_status' => $order->status,
            'source'     => 'system',
        ]);
    }
}
