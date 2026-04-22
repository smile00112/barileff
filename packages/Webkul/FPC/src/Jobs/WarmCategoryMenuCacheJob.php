<?php

namespace Webkul\FPC\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\Category\Services\CategoryMenuCacheService;

/**
 * Rebuilds the category-menu cache for every channel x locale x inventory-source
 * combination and clears the HTTP response cache so the updated trees are served.
 */
class WarmCategoryMenuCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(CategoryMenuCacheService $cacheService): void
    {
        $lock = Cache::lock('warm-category-menu-cache', 120);

        if (! $lock->get()) {
            Log::info('[FPC] WarmCategoryMenuCacheJob skipped: another warm-up is in progress.');

            return;
        }

        try {
            Log::info('[FPC] WarmCategoryMenuCacheJob started.');

            $cacheService->invalidateAll();
            $cacheService->warmAll();

            // Clear the HTTP response cache so /api/categories/tree
            // returns the freshly built tree on next request.
            if (config('responsecache.enabled', false)) {
                ResponseCache::clear();
            }

            Log::info('[FPC] WarmCategoryMenuCacheJob completed.');
        } finally {
            $lock->release();
        }
    }
}
