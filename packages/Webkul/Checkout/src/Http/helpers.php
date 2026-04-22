<?php

use Webkul\Checkout\Facades\Cart;

if (! function_exists('cart')) {
    /**
     * Cart helper.
     *
     * @return Webkul\Checkout\Cart
     */
    function cart()
    {
        return Cart::getFacadeRoot();
    }
}

if (! function_exists('getCurrentInventorySourceId')) {
    /**
     * Get the current inventory source ID from cart or session.
     * Used to filter products by delivery zone's inventory source.
     */
    function getCurrentInventorySourceId(): ?int
    {
        $cart = Cart::getCart();

        if ($cart?->inventory_source_id) {
            return (int) $cart->inventory_source_id;
        }

        $sessionId = session('selected_inventory_source_id');

        if ($sessionId !== null) {
            return (int) $sessionId;
        }

        // Allow cache warm-up requests to declare inventory source via query param.
        $queryId = request()->query('inventory_source_id');

        if ($queryId !== null) {
            return (int) $queryId;
        }

        $default = core()->getCurrentChannel()
            ->inventory_sources()
            ->where('status', 1)
            ->orderBy('inventory_sources.id')
            ->first();

        return $default?->id;
    }
}
