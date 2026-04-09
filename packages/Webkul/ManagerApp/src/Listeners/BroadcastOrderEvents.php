<?php

namespace Webkul\ManagerApp\Listeners;

use Illuminate\Support\Facades\Event;
use Webkul\ManagerApp\Events\OrderCreated;
use Webkul\Sales\Contracts\Order;

class BroadcastOrderEvents
{
    /**
     * Broadcast a new order to the warehouse channel after it is saved.
     *
     * Listens to: checkout.order.save.after
     */
    public function handle(Order $order): void
    {
        if ($order->inventory_source_id === null) {
            return;
        }

        Event::dispatch(new OrderCreated($order));
    }
}
