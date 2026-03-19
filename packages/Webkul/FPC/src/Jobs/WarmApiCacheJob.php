<?php

namespace Webkul\FPC\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\Category\Repositories\CategoryRepository;

class WarmApiCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(CategoryRepository $categoryRepository): void
    {
        $lock = Cache::lock('warm-api-cache', 60);

        if (! $lock->get()) {
            Log::info('[FPC] WarmApiCacheJob skipped: another warm-up is in progress.');

            return;
        }

        try {
            $baseUrl = config('app.url');

            $urls = $this->getStaticUrls();

            $rootCategoryId = core()->getCurrentChannel()->root_category_id;
            $categories = $categoryRepository->getVisibleCategoryTree($rootCategoryId);

            $this->collectCategoryUrls($categories, $urls);

            foreach ($urls as $url) {
                try {
                    Http::timeout(15)->get($baseUrl.$url);
                } catch (\Throwable $e) {
                    Log::warning("[FPC] Warm-up failed for {$url}: {$e->getMessage()}");
                }
            }

            Log::info('[FPC] WarmApiCacheJob completed: '.count($urls).' URLs warmed.');
        } finally {
            $lock->release();
        }
    }

    /**
     * @return string[]
     */
    protected function getStaticUrls(): array
    {
        return [
            '/api/categories',
            '/api/categories/tree',
            '/api/categories/attributes',
            '/api/core/countries',
            '/api/core/states',
            '/api/delivery-zones',
            '/api/products',
        ];
    }

    /**
     * Recursively collect category-based warm-up URLs.
     *
     * @param  \Illuminate\Support\Collection  $categories
     * @param  string[]  $urls
     */
    protected function collectCategoryUrls($categories, array &$urls): void
    {
        foreach ($categories as $category) {
            $urls[] = '/api/products?category_id='.$category->id;
            $urls[] = '/api/categories/max-price/'.$category->id;

            if ($category->children->isNotEmpty()) {
                $this->collectCategoryUrls($category->children, $urls);
            }
        }
    }
}
