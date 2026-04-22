<?php

namespace Webkul\FPC\Listeners;

use Webkul\FPC\Jobs\WarmCategoryMenuCacheJob;

/**
 * Listens to inventory-change events and schedules a category menu cache rebuild.
 *
 * A small delay is applied so that rapid bulk-import saves (e.g. CSV import)
 * do not trigger dozens of rebuild jobs - the queue deduplication or the
 * Cache::lock() inside the job will coalesce concurrent runs.
 */
class InventoryChange
{
    public function onUpdate(mixed $inventory): void
    {
        WarmCategoryMenuCacheJob::dispatch()->delay(now()->addSeconds(5));
    }
}
