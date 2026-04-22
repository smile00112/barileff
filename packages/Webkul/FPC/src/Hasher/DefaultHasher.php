<?php

namespace Webkul\FPC\Hasher;

use Illuminate\Http\Request;
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

            // inventory_source_id is factored into the cache suffix for product
            // endpoints so that warm-up requests and session-based real requests
            // share the same URL hash component.
            if (str_starts_with($request->getPathInfo(), '/api/products')) {
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

        // Differentiate product listing cache by inventory source so that
        // switching the active warehouse returns the correct product set.
        if (str_starts_with($request->getPathInfo(), '/api/products')) {
            $cacheNameSuffix .= '-'.(getCurrentInventorySourceId() ?? 0);
        }

        $cacheNameSuffix .= '-'.$this->cacheProfile->useCacheNameSuffix($request);

        return $cacheNameSuffix;
    }
}
