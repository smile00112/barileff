<?php

namespace Webkul\Admin\Listeners;

use Webkul\Admin\Jobs\InvalidateCategoryProductCountCache;
use Webkul\Product\Models\ProductProxy;

class Category
{
    /**
     * Old category IDs captured before a product update, keyed by product ID.
     */
    private static array $oldCategoryIds = [];

    /**
     * Invalidate cache for categories a newly created product belongs to.
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     */
    public function afterProductCreated($product): void
    {
        $ids = $product->categories()->pluck('id')->all();

        if (! empty($ids)) {
            InvalidateCategoryProductCountCache::dispatch($ids);
        }
    }

    /**
     * Snapshot the category IDs of a product before it is updated.
     */
    public function beforeProductUpdated(int $id): void
    {
        $product = ProductProxy::modelClass()::find($id);

        if ($product) {
            static::$oldCategoryIds[$id] = $product->categories()->pluck('id')->all();
        }
    }

    /**
     * Invalidate cache for all categories affected by a product update
     * (old categories that were removed + new categories that were added).
     *
     * @param  \Webkul\Product\Contracts\Product  $product
     */
    public function afterProductUpdated($product): void
    {
        $oldIds = static::$oldCategoryIds[$product->id] ?? [];
        $newIds = $product->categories()->pluck('id')->all();

        unset(static::$oldCategoryIds[$product->id]);

        $ids = array_values(array_unique(array_merge($oldIds, $newIds)));

        if (! empty($ids)) {
            InvalidateCategoryProductCountCache::dispatch($ids);
        }
    }

    /**
     * Invalidate cache for categories a product belongs to before it is deleted.
     */
    public function beforeProductDeleted(int $id): void
    {
        $product = ProductProxy::modelClass()::find($id);

        if (! $product) {
            return;
        }

        $ids = $product->categories()->pluck('id')->all();

        if (! empty($ids)) {
            InvalidateCategoryProductCountCache::dispatch($ids);
        }
    }
}
