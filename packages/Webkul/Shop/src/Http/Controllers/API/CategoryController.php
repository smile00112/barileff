<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Attribute\Enums\AttributeTypeEnum;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Services\CategoryMenuCacheService;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\AttributeOptionResource;
use Webkul\Shop\Http\Resources\AttributeResource;
use Webkul\Shop\Http\Resources\CategoryResource;
use Webkul\Shop\Http\Resources\CategoryTreeResource;

/**
 * Категории: список, дерево, атрибуты и фильтры.
 *
 * @group Категории
 */
class CategoryController extends APIController
{
    /**
     * Создать экземпляр контроллера.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected CategoryRepository $categoryRepository,
        protected CategoryMenuCacheService $categoryMenuCacheService,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Получить все категории.
     */
    public function index(): JsonResource
    {
        /**
         * Параметры по умолчанию. По умолчанию возвращаются только
         * активные категории в текущей локали.
         */
        $defaultParams = [
            'status' => 1,
            'locale' => app()->getLocale(),
        ];

        $categories = $this->categoryRepository->getAll(array_merge($defaultParams, request()->all()));

        return CategoryResource::collection($categories);
    }

    /**
     * Получить дерево категорий (фильтруется по наличию товаров в источнике инвентаризации).
     */
    public function tree(): JsonResource
    {
        $channel = core()->getCurrentChannel();
        $inventorySourceId = getCurrentInventorySourceId();

        $categories = $this->categoryMenuCacheService->get(
            rootCategoryId: $channel->root_category_id,
            inventorySourceId: $inventorySourceId,
            channelCode: $channel->code,
            locale: app()->getLocale(),
        );

        return CategoryTreeResource::collection($categories);
    }

    /**
     * Получить фильтруемые атрибуты категории.
     */
    public function getAttributes(): JsonResource
    {
        if (! request('category_id')) {
            $filterableAttributes = $this->attributeRepository->getFilterableAttributes();

            return AttributeResource::collection($filterableAttributes);
        }

        $category = $this->categoryRepository->findOrFail(request('category_id'));

        if (empty($filterableAttributes = $category->filterableAttributes)) {
            $filterableAttributes = $this->attributeRepository->getFilterableAttributes();
        }

        return AttributeResource::collection($filterableAttributes);
    }

    /**
     * Получить опции атрибута с пагинацией и поиском.
     *
     * @urlParam attribute_id int required ID атрибута. Example: 1
     *
     * @response 200 {"data": []}
     */
    public function getAttributeOptions(int $attributeId): mixed
    {
        $attribute = $this->attributeRepository->findOrFail($attributeId);

        if ($attribute->type === AttributeTypeEnum::BOOLEAN->value) {
            return new JsonResponse([
                'data' => AttributeTypeEnum::getBooleanOptions(),
            ]);
        }

        $query = $attribute->options()
            ->with([
                'translation' => fn ($query) => $query->where('locale', core()->getCurrentLocale()->code),
            ]);

        if ($search = request('search')) {
            $query->where(function ($query) use ($search) {
                $query->whereHas('translation', fn ($query) => $query->where('label', 'like', "%{$search}%"))
                    ->orWhere('admin_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('sort_order');

        return AttributeOptionResource::collection($query->paginate());
    }

    /**
     * Получить максимальную цену товаров.
     *
     * @urlParam id int ID категории. Example: 1
     */
    public function getProductMaxPrice(?int $id = null): JsonResource
    {
        if (core()->getConfigData('catalog.products.search.engine') == 'elastic') {
            $searchEngine = core()->getConfigData('catalog.products.search.storefront_mode');
        }

        $maxPrice = $this->productRepository
            ->setSearchEngine($searchEngine ?? 'database')
            ->getMaxPrice(['category_id' => $id]);

        return new JsonResource([
            'max_price' => core()->convertPrice($maxPrice),
        ]);
    }
}
