<?php

namespace Webkul\Category\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Webkul\Category\Contracts\Category;
use Webkul\Category\Models\CategoryTranslationProxy;
use Webkul\Core\Eloquent\Repository;

class CategoryRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Category::class;
    }

    /**
     * Get categories.
     *
     * @return void
     */
    public function getAll(array $params = [])
    {
        $queryBuilder = $this->query()
            ->select('categories.*')
            ->leftJoin('category_translations', 'category_translations.category_id', '=', 'categories.id');

        foreach ($params as $key => $value) {
            switch ($key) {
                case 'name':
                    $queryBuilder->where('category_translations.name', 'like', '%'.urldecode($value).'%');

                    break;
                case 'description':
                    $queryBuilder->where('category_translations.description', 'like', '%'.urldecode($value).'%');

                    break;
                case 'status':
                    $queryBuilder->where('categories.status', $value);

                    break;
                case 'only_children':
                    $queryBuilder->whereNotNull('categories.parent_id');

                    break;
                case 'parent_id':
                    $parentIds = array_filter(array_map('trim', explode(',', $value)));
                    $queryBuilder->whereIn('categories.parent_id', $parentIds);

                    break;
                case 'locale':
                    $queryBuilder->where('category_translations.locale', $value);

                    break;

                case 'inventory_source_id':
                    $inventorySourceId = (int) $value;

                    if (
                        $inventorySourceId > 0
                        && core()->getConfigData('catalog.products.settings.filter_categories_by_stock')
                    ) {
                        $stockedIds = Cache::remember(
                            "category-stocked-ids:{$inventorySourceId}",
                            3600,
                            fn () => $this->getCategoryIdsWithStockForSource($inventorySourceId)
                        );

                        if (! empty($stockedIds)) {
                            $queryBuilder->whereIn('categories.id', $stockedIds);
                        } else {
                            $queryBuilder->whereRaw('1 = 0');
                        }
                    }

                    break;
            }
        }

        return $queryBuilder->paginate($params['limit'] ?? 10);
    }

    /**
     * Create category.
     *
     * @return Category
     */
    public function create(array $data)
    {
        if (
            isset($data['locale'])
            && $data['locale'] == 'all'
        ) {
            $model = app()->make($this->model());

            foreach (core()->getAllLocales() as $locale) {
                foreach ($model->translatedAttributes as $attribute) {
                    if (isset($data[$attribute])) {
                        $data[$locale->code][$attribute] = $data[$attribute];

                        $data[$locale->code]['locale_id'] = $locale->id;
                    }
                }
            }
        }

        $category = $this->model->create($data);

        $this->uploadImages($data, $category);

        $this->uploadImages($data, $category, 'banner_path');

        if (isset($data['attributes'])) {
            $category->filterableAttributes()->sync($data['attributes']);
        }

        return $category;
    }

    /**
     * Update category.
     *
     * @param  int  $id
     * @param  string  $attribute
     * @return Category
     */
    public function update(array $data, $id)
    {
        $category = $this->find($id);

        $data = $this->setSameAttributeValueToAllLocale($data, 'slug');

        $category->update($data);

        $this->uploadImages($data, $category);

        $this->uploadImages($data, $category, 'banner_path');

        if (isset($data['attributes'])) {
            $category->filterableAttributes()->sync($data['attributes']);
        }

        return $category;
    }

    /**
     * Specify category tree.
     *
     * @return Category
     */
    public function getCategoryTree(?int $id = null)
    {
        return $id
            ? $this->model::orderBy('position', 'ASC')->where('id', '!=', $id)->get()->toTree()
            : $this->model::orderBy('position', 'ASC')->get()->toTree();
    }

    /**
     * Specify category tree.
     *
     * @return Collection
     */
    public function getCategoryTreeWithoutDescendant(?int $id = null)
    {
        return $id
            ? $this->model::orderBy('position', 'ASC')->where('id', '!=', $id)->whereNotDescendantOf($id)->get()->toTree()
            : $this->model::orderBy('position', 'ASC')->get()->toTree();
    }

    /**
     * Get root categories.
     *
     * @return Collection
     */
    public function getRootCategories()
    {
        return $this->getModel()->where('parent_id', null)->get();
    }

    /**
     * Get child categories.
     *
     * @return Collection
     */
    public function getChildCategories($parentId)
    {
        return $this->getModel()->where('parent_id', $parentId)->get();
    }

    /**
     * get visible category tree.
     *
     * @param  int  $id
     * @return Collection
     */
    public function getVisibleCategoryTree($id = null)
    {
        return $id
            ? $this->model::orderBy('position', 'ASC')->where('status', 1)->descendantsAndSelf($id)->toTree($id)
            : $this->model::orderBy('position', 'ASC')->where('status', 1)->get()->toTree();
    }

    /**
     * Get category IDs that have at least one product with stock > 0 for the given inventory source.
     *
     * Always includes categories that contain virtual or downloadable products
     * (these are not warehouse-specific). Uses a single efficient SQL query.
     *
     * @return int[]
     */
    public function getCategoryIdsWithStockForSource(int $inventorySourceId): array
    {
        $prefix = DB::getTablePrefix();

        $rows = DB::select("
            SELECT DISTINCT pc.category_id
            FROM {$prefix}product_categories pc
            JOIN {$prefix}products p ON p.id = pc.product_id
            WHERE p.parent_id IS NULL
              AND (
                  -- always include virtual / downloadable (not warehouse-specific)
                  p.type IN ('virtual', 'downloadable')

                  -- simple / configurable: direct inventory on this source > 0
                  OR EXISTS (
                      SELECT 1 FROM {$prefix}product_inventories pi1
                      WHERE pi1.product_id = p.id
                        AND pi1.inventory_source_id = ?
                        AND pi1.qty > 0
                  )

                  -- configurable: any variant has inventory on this source > 0
                  OR EXISTS (
                      SELECT 1 FROM {$prefix}products var
                      JOIN {$prefix}product_inventories pi2
                          ON pi2.product_id = var.id
                         AND pi2.inventory_source_id = ?
                         AND pi2.qty > 0
                      WHERE var.parent_id = p.id
                  )
              )
        ", [$inventorySourceId, $inventorySourceId]);

        return array_column($rows, 'category_id');
    }

    /**
     * Checks slug is unique or not based on locale.
     *
     * @param  int  $id
     * @param  string  $slug
     * @return bool
     */
    public function isSlugUnique($id, $slug)
    {
        $exists = CategoryTranslationProxy::modelClass()::where('category_id', '<>', $id)
            ->where('slug', $slug)
            ->limit(1)
            ->select(DB::raw(1))
            ->exists();

        return ! $exists;
    }

    /**
     * Retrieve category from slug.
     *
     * @param  string  $slug
     * @return Category
     */
    public function findBySlug($slug)
    {
        if ($category = $this->model->whereTranslation('slug', $slug)->first()) {
            return $category;
        }
    }

    /**
     * Retrieve category from slug.
     *
     * @param  string  $slug
     * @return Category
     */
    public function findBySlugOrFail($slug)
    {
        return $this->model->whereTranslation('slug', $slug)->firstOrFail();
    }

    /**
     * Upload category's images.
     *
     * @param  array  $data
     * @param  Category  $category
     * @param  string  $type
     * @return void
     */
    public function uploadImages($data, $category, $type = 'logo_path')
    {
        if (isset($data[$type])) {
            foreach ($data[$type] as $imageId => $image) {
                $file = $type.'.'.$imageId;

                if (request()->hasFile($file)) {
                    if ($category->{$type}) {
                        Storage::delete($category->{$type});
                    }

                    $manager = new ImageManager;

                    $image = $manager->make(request()->file($file))->encode('webp');

                    $category->{$type} = 'category/'.$category->id.'/'.Str::random(40).'.webp';

                    Storage::put($category->{$type}, $image);

                    $category->save();
                }
            }
        } else {
            if ($category->{$type}) {
                Storage::delete($category->{$type});
            }

            $category->{$type} = null;

            $category->save();
        }
    }

    /**
     * Get partials.
     *
     * @param  array|null  $columns
     * @return array
     */
    public function getPartial($columns = null)
    {
        $categories = $this->model->all();

        $trimmed = [];

        foreach ($categories as $key => $category) {
            if (! empty($category->name)) {
                $trimmed[$key] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            }
        }

        return $trimmed;
    }

    /**
     * Set same value to all locales in category.
     *
     * To Do: Move column from the `category_translations` to `category` table. And remove
     * this created method.
     *
     * @param  string  $attributeNames
     * @return array
     */
    private function setSameAttributeValueToAllLocale(array $data, ...$attributeNames)
    {
        $requestedLocale = core()->getRequestedLocaleCode();

        $model = app()->make($this->model());

        foreach ($attributeNames as $attributeName) {
            foreach (core()->getAllLocales() as $locale) {
                if ($requestedLocale == $locale->code) {
                    foreach ($model->translatedAttributes as $attribute) {
                        if ($attribute === $attributeName) {
                            $data[$locale->code][$attribute] = $data[$requestedLocale][$attribute] ?? $data[$data['locale']][$attribute];
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Find category id that is a direct child of parent with the given translated name.
     */
    public function findIdByParentAndName(int $parentId, string $name, string $locale): ?int
    {
        $id = DB::table('categories')
            ->join('category_translations', 'category_translations.category_id', '=', 'categories.id')
            ->where('categories.parent_id', $parentId)
            ->where('category_translations.locale', $locale)
            ->where('category_translations.name', $name)
            ->value('categories.id');

        if ($id !== null) {
            return (int) $id;
        }

        return $this->findIdByParentAndNameAnyLocale($parentId, $name);
    }

    /**
     * Same as findIdByParentAndName but ignores locale (used when only one translation row exists).
     */
    protected function findIdByParentAndNameAnyLocale(int $parentId, string $name): ?int
    {
        $id = DB::table('categories')
            ->join('category_translations', 'category_translations.category_id', '=', 'categories.id')
            ->where('categories.parent_id', $parentId)
            ->where('category_translations.name', $name)
            ->value('categories.id');

        return $id !== null ? (int) $id : null;
    }

    /**
     * Resolve an ordered category path under an anchor (no creation). Returns empty array if any segment is missing.
     *
     * @param  array<int, string>  $segments
     * @return array<int, int>
     */
    public function resolveCategoryChainUnderParent(int $anchorId, array $segments, string $locale): array
    {
        $ids = [];
        $currentParentId = $anchorId;

        foreach ($segments as $name) {
            $id = $this->findIdByParentAndName($currentParentId, $name, $locale);

            if ($id === null) {
                return [];
            }

            $ids[] = $id;
            $currentParentId = $id;
        }

        return $ids;
    }

    /**
     * Ensure an ordered category path exists under an anchor, creating missing nodes.
     *
     * @param  array<int, string>  $segments
     * @return array<int, int>
     */
    public function ensureCategoryChainUnderParent(int $anchorId, array $segments, string $locale): array
    {
        $ids = [];
        $currentParentId = $anchorId;

        foreach ($segments as $name) {
            $id = $this->findIdByParentAndName($currentParentId, $name, $locale);

            if ($id === null) {
                $slug = $this->generateUniqueCategorySlug($name);

                $category = $this->create([
                    'locale' => 'all',
                    'name' => $name,
                    'description' => '',
                    'meta_title' => '',
                    'meta_description' => '',
                    'meta_keywords' => '',
                    'slug' => $slug,
                    'position' => 1,
                    'status' => 1,
                    'display_mode' => 'products_and_description',
                    'parent_id' => $currentParentId,
                ]);

                $id = (int) $category->id;

                $priceAttributeId = $this->getPriceAttributeId();

                if ($priceAttributeId !== null) {
                    $category->filterableAttributes()->attach($priceAttributeId);
                }
            }

            $ids[] = $id;
            $currentParentId = $id;
        }

        return $ids;
    }

    /**
     * Get the ID of the price attribute, cached per request.
     */
    protected function getPriceAttributeId(): ?int
    {
        static $priceAttributeId = false;

        if ($priceAttributeId === false) {
            $priceAttributeId = DB::table('attributes')->where('code', 'price')->value('id');
            $priceAttributeId = $priceAttributeId !== null ? (int) $priceAttributeId : null;
        }

        return $priceAttributeId;
    }

    /**
     * Generate a unique slug for category_translations.
     */
    protected function generateUniqueCategorySlug(string $name): string
    {
        $slug = Str::slug($name);

        if ($slug === '' || DB::table('category_translations')->where('slug', $slug)->exists()) {
            $slug = Str::slug($name).'-'.substr(md5($name.microtime()), 0, 6);
        }

        return $slug;
    }
}
