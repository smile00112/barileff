<?php

namespace Webkul\FPC\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Category\Services\CategoryMenuCacheService;
use Webkul\FPC\Jobs\WarmCategoryMenuCacheJob;

/**
 * Warms the category-menu cache for all channels, locales, and inventory sources.
 *
 * Usage:
 *   php artisan category:warm-menu-cache           # runs synchronously
 *   php artisan category:warm-menu-cache --queue   # dispatches to queue
 */
class WarmCategoryMenuCacheCommand extends Command
{
    protected $signature = 'category:warm-menu-cache
                            {--queue : Dispatch as a queued job instead of running synchronously}';

    protected $description = 'Warm the category menu cache for all channels, locales, and inventory sources.';

    public function handle(CategoryMenuCacheService $cacheService): int
    {
        if ($this->option('queue')) {
            WarmCategoryMenuCacheJob::dispatch();
            $this->info('WarmCategoryMenuCacheJob dispatched to the queue.');

            return self::SUCCESS;
        }

        $this->info('Invalidating category menu cache...');
        $cacheService->invalidateAll();

        $this->info('Building category menu cache...');
        $cacheService->warmAll();

        $this->info('Category menu cache warmed successfully.');

        return self::SUCCESS;
    }
}
