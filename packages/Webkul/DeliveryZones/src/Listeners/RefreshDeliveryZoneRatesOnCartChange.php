<?php

namespace Webkul\DeliveryZones\Listeners;

use Webkul\Checkout\Contracts\Cart as CartContract;
use Webkul\Checkout\Facades\Cart;
use Webkul\Shipping\Facades\Shipping;

class RefreshDeliveryZoneRatesOnCartChange
{
    /**
     * Recalculate delivery zone shipping rates when cart changes.
     * Rates depend on cart sub_total (min_order_total conditions).
     */
    public function handle(CartContract $cart): void
    {
        if ($cart->shipping_method !== 'delivery_zones_delivery_zones') {
            return;
        }

        if (! $cart->haveStockableItems()) {
            return;
        }

        if (! $cart->shipping_address) {
            return;
        }

        Shipping::collectRates();

        Cart::refreshCart();
    }
}
