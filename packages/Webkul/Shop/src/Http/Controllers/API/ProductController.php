<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
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
        $mem = fn () => round(memory_get_usage(true) / 1024 / 1024, 2).' MB';
        $ctx = ['category_id' => request()->query('category_id'), 'url' => request()->fullUrl()];

        Log::debug('[ProductAPI] start index', array_merge($ctx, ['mem' => $mem()]));

        $searchEngine = 'database';

        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        Log::debug('[ProductAPI] search engine resolved', ['engine' => $searchEngine, 'mem' => $mem()]);

        $searchData = $this->resolveSearchQueryData($searchEngine);

        $query = $searchData['effective_query'] ?? $searchData['original_query'];

        Log::debug('[ProductAPI] query resolved', ['query' => $query, 'mem' => $mem()]);

        $categoryIds = request()->filled('category_id')
            ? $this->resolveCategoryIds((int) request()->query('category_id'))
            : null;

        Log::debug('[ProductAPI] category ids resolved', ['category_ids' => $categoryIds, 'mem' => $mem()]);

        $inventorySourceId = getCurrentInventorySourceId();

        Log::debug('[ProductAPI] calling getAll', ['inventory_source_id' => $inventorySourceId, 'mem' => $mem()]);

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

        Log::debug('[ProductAPI] getAll done', ['total' => $products->total(), 'count' => $products->count(), 'mem' => $mem()]);

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

        Log::debug('[ProductAPI] building resource collection', ['mem' => $mem()]);

        $result = ProductResource::collection($products);

        Log::debug('[ProductAPI] resource collection built', ['mem' => $mem()]);

        return $result;
    }

    /**
     * Resolve the current category together with all descendant category ids.
     */
    protected function resolveCategoryIds(int $categoryId): string
    {
        $mem = fn () => round(memory_get_usage(true) / 1024 / 1024, 2).' MB';

        Log::debug('[ProductAPI] resolveCategoryIds start', ['category_id' => $categoryId, 'mem' => $mem()]);

        $result = Cache::remember("cat_desc_{$categoryId}", 3600, function () use ($categoryId, $mem) {
            Log::debug('[ProductAPI] resolveCategoryIds cache miss, querying DB', ['category_id' => $categoryId, 'mem' => $mem()]);

            $category = $this->categoryRepository->find($categoryId);

            if (! $category) {
                Log::debug('[ProductAPI] resolveCategoryIds category not found', ['category_id' => $categoryId]);

                return (string) $categoryId;
            }

            $ids = $category->descendants()
                ->pluck('id')
                ->prepend($category->id)
                ->implode(',');

            Log::debug('[ProductAPI] resolveCategoryIds descendants resolved', ['ids_count' => substr_count($ids, ',') + 1, 'ids_preview' => mb_substr($ids, 0, 200), 'mem' => $mem()]);

            return $ids;
        });

        Log::debug('[ProductAPI] resolveCategoryIds done', ['result_preview' => mb_substr($result, 0, 200), 'mem' => $mem()]);

        return $result;
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
