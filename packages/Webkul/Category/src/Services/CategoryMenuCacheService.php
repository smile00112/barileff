<?php

namespace Webkul\Category\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Core;

class CategoryMenuCacheService
{
    /**
     * How long to keep the category menu tree in cache (seconds).
     */
    private const TTL = 86400; // 24 hours

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly Core $core,
    ) {}

    /**
     * Get the filtered category tree for the given parameters.
     *
     * The result is served from cache when available.  If $inventorySourceId
     * is null, or filtering is disabled via admin config, the full visible
     * tree is returned (unfiltered).
     */
    public function get(int $rootCategoryId, ?int $inventorySourceId, string $channelCode, string $locale): Collection
    {
        if (! core()->getConfigData('catalog.products.settings.filter_categories_by_stock')) {
            $inventorySourceId = null;
        }

        $key = $this->cacheKey($channelCode, $locale, $inventorySourceId);

        return Cache::remember($key, self::TTL, function () use ($rootCategoryId, $inventorySourceId) {
            $tree = $this->categoryRepository->getVisibleCategoryTree($rootCategoryId);

            if ($inventorySourceId === null) {
                return $tree;
            }

            $stockedIds = $this->categoryRepository->getCategoryIdsWithStockForSource($inventorySourceId);

            return $this->pruneTree($tree, $stockedIds);
        });
    }

    /**
     * Warm the cache for every channel x locale x inventory source combination.
     */
    public function warmAll(): void
    {
        foreach ($this->core->getAllChannels() as $channel) {
            $rootId = $channel->root_category_id;
            $channelCode = $channel->code;

            $sources = $channel->inventory_sources()
                ->where('status', 1)
                ->get(['inventory_sources.id']);

            foreach ($this->core->getAllLocales() as $locale) {
                // Cache unfiltered tree (used when no source is selected)
                $this->forgetKey($channelCode, $locale->code, null);
                $tree = $this->categoryRepository->getVisibleCategoryTree($rootId);
                Cache::put($this->cacheKey($channelCode, $locale->code, null), $tree, self::TTL);

                foreach ($sources as $source) {
                    $key = $this->cacheKey($channelCode, $locale->code, $source->id);
                    $stockedIds = $this->categoryRepository->getCategoryIdsWithStockForSource($source->id);
                    $sourceTree = $this->categoryRepository->getVisibleCategoryTree($rootId);
                    $filtered = $this->pruneTree($sourceTree, $stockedIds);
                    Cache::put($key, $filtered, self::TTL);
                }
            }
        }
    }

    /**
     * Forget all cached trees for every channel x locale x inventory source.
     */
    public function invalidateAll(): void
    {
        foreach ($this->core->getAllChannels() as $channel) {
            $channelCode = $channel->code;

            $sources = $channel->inventory_sources()
                ->get(['inventory_sources.id']);

            foreach ($this->core->getAllLocales() as $locale) {
                $this->forgetKey($channelCode, $locale->code, null);

                foreach ($sources as $source) {
                    $this->forgetKey($channelCode, $locale->code, $source->id);
                    $this->categoryRepository->forgetStockedIdsCache($source->id);
                }
            }
        }
    }

    /**
     * Forget the cache key for the current request context.
     */
    public function invalidateCurrent(?int $inventorySourceId): void
    {
        $channel = $this->core->getCurrentChannel();
        $locale = app()->getLocale();
        $this->forgetKey($channel->code, $locale, $inventorySourceId);
    }

    /**
     * Build the cache key for the given combination.
     */
    public function cacheKey(string $channelCode, string $locale, ?int $inventorySourceId): string
    {
        return 'category-menu-tree:'.$channelCode.':'.$locale.':'.($inventorySourceId ?? 0);
    }

    private function forgetKey(string $channelCode, string $locale, ?int $inventorySourceId): void
    {
        Cache::forget($this->cacheKey($channelCode, $locale, $inventorySourceId));
    }

    /**
     * Recursively remove category nodes that have no stocked products and no
     * children with stocked products.
     *
     * @param  int[]  $stockedCategoryIds
     */
    private function pruneTree(Collection $nodes, array $stockedCategoryIds): Collection
    {
        return $nodes->map(function ($category) use ($stockedCategoryIds) {
            $prunedChildren = $this->pruneTree($category->children, $stockedCategoryIds);

            $category->setRelation('children', $prunedChildren);

            return $category;
        })->filter(function ($category) use ($stockedCategoryIds) {
            return in_array($category->id, $stockedCategoryIds, true)
                || $category->children->isNotEmpty();
        })->values();
    }
}
