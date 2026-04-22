<?php

namespace Webkul\Product\Observers;

use Webkul\Product\Models\ProductInventory;

/**
 * Fires a custom event whenever a product inventory record is created, updated
 * in a way that crosses the zero boundary, or deleted with non-zero qty.
 *
 * The FPC package listens to this event and re-warms the category menu cache.
 */
class ProductInventoryObserver
{
    public function created(ProductInventory $inventory): void
    {
        if ($inventory->qty > 0) {
            event('catalog.product.inventory.update.after', $inventory);
        }
    }

    public function updated(ProductInventory $inventory): void
    {
        $oldQty = $inventory->getOriginal('qty', 0);
        $newQty = $inventory->qty;

        $crossedZero = ($oldQty == 0 && $newQty > 0) || ($oldQty > 0 && $newQty == 0);

        if ($crossedZero) {
            event('catalog.product.inventory.update.after', $inventory);
        }
    }

    public function deleted(ProductInventory $inventory): void
    {
        if ($inventory->qty > 0) {
            event('catalog.product.inventory.update.after', $inventory);
        }
    }
}
