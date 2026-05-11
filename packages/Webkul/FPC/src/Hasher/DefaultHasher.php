<?php

namespace Webkul\FPC\Hasher;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Hasher\DefaultHasher as BaseDefaultHasher;

class DefaultHasher extends BaseDefaultHasher
{
    /**
     * Get the hash for the given request.
     */
    protected function getNormalizedRequestUri(Request $request): string
    {
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            $params = $request->query();

            // inventory_source_id is factored into the cache suffix (not the URL component)
            // for endpoints whose response varies by warehouse. This ensures warm-up requests
            // (which pass ?inventory_source_id=N explicitly) and session-based real requests
            // (which rely on the suffix) resolve to the same cache key.
            if (
                str_starts_with($request->getPathInfo(), '/api/products')
                || str_starts_with($request->getPathInfo(), '/api/categories')
            ) {
                unset($params['inventory_source_id']);
            }

            ksort($params);

            $queryString = $params ? '?'.http_build_query($params) : '';

            return $request->getBaseUrl().$request->getPathInfo().$queryString;
        }

        if (
            $request->routeIs('shop.search.index')
            && $request->has('query')
        ) {
            $queryString = "?query={$request->query('query')}";

            return $request->getBaseUrl().$request->getPathInfo().$queryString;
        }

        return $request->getBaseUrl().$request->getPathInfo();
    }

    /**
     * Resolve inventory source ID for the cache key without loading the cart.
     * Cart::getCart() hits the DB on every request; session is already loaded.
     */
    protected function resolveInventorySourceIdForCache(Request $request): int
    {
        // Query param used by cache warm-up jobs.
        $queryId = $request->query('inventory_source_id');
        if ($queryId !== null) {
            return (int) $queryId;
        }

        // Session is loaded by the web middleware before the hasher runs.
        $sessionId = session('selected_inventory_source_id');
        if ($sessionId !== null) {
            return (int) $sessionId;
        }

        // Fall back to the channel's default source — cached to avoid repeated DB hits.
        return (int) Cache::remember(
            'fpc_default_inv_src_'.core()->getCurrentChannel()->id,
            3600,
            fn () => core()->getCurrentChannel()
                ->inventory_sources()
                ->where('status', 1)
                ->orderBy('inventory_sources.id')
                ->first()
                ?->id ?? 0
        );
    }

    /**
     * Get the cache name suffix for the given request.
     */
    protected function getCacheNameSuffix(Request $request): string
    {
        if ($request->attributes->has('responsecache.cacheNameSuffix')) {
            return $request->attributes->get('responsecache.cacheNameSuffix');
        }

        $cacheNameSuffix = core()->getCurrentChannel()->code
            .'-'.core()->getCurrentLocale()->code
            .'-'.core()->getCurrentCurrency()->code;

        // Differentiate product and category listing caches by inventory source so that
        // switching the active warehouse returns the correct filtered set.
        // We deliberately avoid Cart::getCart() here (expensive DB load) and rely on
        // the session value that gets written whenever the user selects a delivery zone.
        if (
            str_starts_with($request->getPathInfo(), '/api/products')
            || str_starts_with($request->getPathInfo(), '/api/categories')
        ) {
            $cacheNameSuffix .= '-'.$this->resolveInventorySourceIdForCache($request);
        }

        $cacheNameSuffix .= '-'.$this->cacheProfile->useCacheNameSuffix($request);

        return $cacheNameSuffix;
    }
}
