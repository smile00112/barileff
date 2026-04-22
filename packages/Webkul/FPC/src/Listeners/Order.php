<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;

class Order extends Product
{
    /**
     * After order is created
     *
     * @param  \Webkul\Sale\Contracts\Order  $order
     * @return void
     */
    public function afterCancelOrCreate($order)
    {
        foreach ($order->all_items as $item) {
            if (! $item->product) {
                continue;
            }

            $urls = $this->getForgettableUrls($item->product);

            ResponseCache::forget($urls);
        }

        // Inventory levels are filtered live via inventory_source_id, so product
        // listing cache does not need to be invalidated on order creation/cancel.
    }
}
