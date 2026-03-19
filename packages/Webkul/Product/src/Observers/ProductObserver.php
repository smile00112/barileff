<?php

namespace Webkul\Product\Observers;

use Illuminate\Support\Facades\Storage;

class ProductObserver
{
    /**
     * Handle the Product "creating" event — auto-generate SKU if not supplied.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     */
    public function creating($product): void
    {
        if (! empty($product->sku)) {
            return;
        }

        // Timestamp-based unique suffix to avoid race conditions on high-load inserts.
        $suffix = str_pad((string) (time() % 1_000_000), 6, '0', STR_PAD_LEFT);

        $product->sku = 'ART-'.$suffix;
    }

    /**
     * Handle the Product "deleted" event.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     */
    public function deleted($product): void
    {
        Storage::deleteDirectory('product/'.$product->id);
    }
}
