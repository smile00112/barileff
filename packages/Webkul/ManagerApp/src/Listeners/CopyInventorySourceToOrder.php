<?php

namespace Webkul\ManagerApp\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\Sales\Contracts\Order;

class CopyInventorySourceToOrder
{
    /**
     * Copy inventory_source_id from the cart to the order after order creation.
     */
    public function handle(Order $order): void
    {
        if ($order->inventory_source_id !== null) {
            return;
        }

        $inventorySourceId = $order->cart?->inventory_source_id;

        if (! $inventorySourceId) {
            Log::debug('CopyInventorySourceToOrder: cart has no inventory_source_id', [
                'order_id' => $order->id,
                'cart_id'  => $order->cart_id,
            ]);

            return;
        }

        $order->update(['inventory_source_id' => $inventorySourceId]);
    }
}
