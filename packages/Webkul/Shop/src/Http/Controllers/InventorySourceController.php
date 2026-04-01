<?php

namespace Webkul\Shop\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Webkul\Checkout\Facades\Cart;

class InventorySourceController extends Controller
{
    /**
     * Switch inventory source for catalog testing in storefront.
     */
    public function switch(): RedirectResponse
    {
        abort_unless(auth()->guard('admin')->check(), 403);

        $validated = request()->validate([
            'inventory_source_id' => ['nullable', 'integer', 'exists:inventory_sources,id'],
        ]);

        $inventorySourceId = isset($validated['inventory_source_id'])
            ? (int) $validated['inventory_source_id']
            : null;

        if ($inventorySourceId) {
            $belongsToCurrentChannel = core()->getCurrentChannel()
                ->inventory_sources()
                ->where('inventory_sources.id', $inventorySourceId)
                ->exists();

            abort_unless($belongsToCurrentChannel, 422);

            session(['selected_inventory_source_id' => $inventorySourceId]);
        } else {
            session()->forget('selected_inventory_source_id');
        }

        $cart = Cart::getCart();

        if ($cart) {
            $cart->inventory_source_id = $inventorySourceId;
            $cart->save();
        }

        return redirect()->back();
    }
}
