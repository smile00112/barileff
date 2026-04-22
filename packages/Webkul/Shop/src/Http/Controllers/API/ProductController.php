<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Marketing\Jobs\UpdateCreateSearchTerm as UpdateCreateSearchTermJob;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\ProductResource;

/**
 * Товары: список, сопутствующие и апселл-товары.
 *
 * @group Товары
 */
class ProductController extends APIController
{
    /**
     * Создать экземпляр контроллера.
     *
     * @return void
     */
    public function __construct(
        protected CategoryRepository $categoryRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Получить список товаров с поиском и фильтрами.
     *
     * @queryParam query string Поисковый запрос. Example: laptop
     * @queryParam category_id int Фильтр по ID категории. Example: 5
     * @queryParam sort string Поле сортировки (price, created_at и т.д.). Example: price
     * @queryParam order string Направление сортировки: asc или desc. Example: asc
     * @queryParam limit int Количество элементов на странице. Example: 12
     * @queryParam page int Номер страницы пагинации. Example: 1
     */
    public function index(): JsonResource
    {
        // TEMP DEBUG — remove after diagnosing browser vs Postman slowness
        $t0 = microtime(true);
        DB::enableQueryLog();

        $searchEngine = 'database';

        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        $searchData = $this->resolveSearchQueryData($searchEngine);

        $query = $searchData['effective_query'] ?? $searchData['original_query'];

        $categoryIds = request()->filled('category_id')
            ? $this->resolveCategoryIds((int) request()->query('category_id'))
            : null;

        $inventorySourceId = getCurrentInventorySourceId();

        $t1 = microtime(true);

        $products = $this->productRepository
            ->setSearchEngine($searchEngine)
            ->getAll(array_merge(request()->query(), [
                'category_id' => $categoryIds,
                'query' => $query,
                'channel_id' => core()->getCurrentChannel()->id,
                'status' => 1,
                'visible_individually' => 1,
                'inventory_source_id' => $inventorySourceId,
            ]));

        $t2 = microtime(true);

        $queries = DB::getQueryLog();
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        $slowQueries = array_filter($queries, fn ($q) => $q['time'] > 500);

        Log::warning('PERF products/index', [
            'pre_query_ms' => round(($t1 - $t0) * 1000),
            'query_ms' => round(($t2 - $t1) * 1000),
            'total_ms' => round(($t2 - $t0) * 1000),
            'query_count' => count($queries),
            'total_query_time_ms' => round($totalQueryTime),
            'inventory_source_id' => $inventorySourceId,
            'category_id' => request()->query('category_id'),
            'user_id' => auth()->guard()->id(),
            'has_session_cart' => session()->has('cart'),
            'selected_inv_src_session' => session('selected_inventory_source_id'),
            'slow_queries' => array_values(array_map(fn ($q) => [
                'ms' => round($q['time']),
                'sql' => substr($q['query'], 0, 300),
            ], $slowQueries)),
        ]);

        if (! empty($query)) {
            /**
             * Обновить или создать поисковый термин,
             * только если передан один query-фильтр.
             */
            if (count(request()->except(['mode', 'sort', 'limit'])) == 1) {
                UpdateCreateSearchTermJob::dispatch([
                    'term' => $query,
                    'results' => $products->total(),
                    'channel_id' => core()->getCurrentChannel()->id,
                    'locale' => app()->getLocale(),
                ]);
            }
        }

        return ProductResource::collection($products);
    }

    /**
     * Resolve the current category together with all descendant category ids.
     */
    protected function resolveCategoryIds(int $categoryId): string
    {
        return Cache::remember("cat_desc_{$categoryId}", 3600, function () use ($categoryId) {
            $category = $this->categoryRepository->find($categoryId);

            if (! $category) {
                return (string) $categoryId;
            }

            return $category->descendants()
                ->pluck('id')
                ->prepend($category->id)
                ->implode(',');
        });
    }

    /**
     * Сформировать данные поискового запроса.
     */
    protected function resolveSearchQueryData($searchEngine): array
    {
        if (request()->query('suggest', '') === '0') {
            return [
                'original_query' => request()->query('query', ''),
                'effective_query' => null,
            ];
        }

        $originalQuery = request()->query('query', '');

        return [
            'original_query' => $originalQuery,
            'effective_query' => $this->getEffectiveQuery($originalQuery, $searchEngine),
        ];
    }

    /**
     * Вернуть итоговый поисковый запрос с учетом поискового движка.
     */
    protected function getEffectiveQuery(string $originalQuery, string $searchEngine): ?string
    {
        $effectiveQuery = $this->productRepository->setSearchEngine($searchEngine)->getSuggestions($originalQuery);

        return $effectiveQuery;
    }

    /**
     * Получить список сопутствующих товаров.
     *
     * @urlParam id int required ID товара. Example: 1
     *
     * @response 200 {"data": []}
     */
    public function relatedProducts(int $id): JsonResource
    {
        $product = $this->productRepository->findOrFail($id);

        $relatedProducts = $product->related_products()
            ->take(core()->getConfigData('catalog.products.product_view_page.no_of_related_products'))
            ->get();

        return ProductResource::collection($relatedProducts);
    }

    /**
     * Получить список апселл-товаров.
     *
     * @urlParam id int required ID товара. Example: 1
     *
     * @response 200 {"data": []}
     */
    public function upSellProducts(int $id): JsonResource
    {
        $product = $this->productRepository->findOrFail($id);

        $upSellProducts = $product->up_sells()
            ->take(core()->getConfigData('catalog.products.product_view_page.no_of_up_sells_products'))
            ->get();

        return ProductResource::collection($upSellProducts);
    }
}
