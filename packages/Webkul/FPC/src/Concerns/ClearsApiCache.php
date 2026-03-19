<?php

namespace Webkul\FPC\Concerns;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\FPC\Jobs\WarmApiCacheJob;

trait ClearsApiCache
{
    /**
     * Clear API response cache and dispatch warm-up job.
     */
    protected function clearApiCacheAndWarm(): void
    {
        ResponseCache::clear();

        WarmApiCacheJob::dispatch()->delay(now()->addSeconds(5));
    }
}
