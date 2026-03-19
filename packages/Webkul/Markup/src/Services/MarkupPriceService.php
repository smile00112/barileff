<?php

namespace Webkul\Markup\Services;

use Illuminate\Support\Collection;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Markup\Models\MarkupAppliedPriceProxy;
use Webkul\Markup\Models\MarkupConditionProxy;
use Webkul\Markup\Models\MarkupGroupProxy;
use Webkul\Markup\Repositories\MarkupAppliedPriceRepository;
use Webkul\Markup\Repositories\MarkupLogRepository;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Helpers\Indexers\Price as PriceIndexer;
use Webkul\Product\Models\ProductAttributeValueProxy;
use Webkul\Product\Models\ProductProxy;

class MarkupPriceService
{
    protected const COST_ATTRIBUTE_ID = 12;

    /** @var array<string, object> */
    protected array $attributeCache = [];

    public function __construct(
        protected MarkupAppliedPriceRepository $appliedPriceRepository,
        protected MarkupLogRepository $logRepository,
    ) {}

    /**
     * Apply markup/discount prices for a group.
     */
    public function apply(object $group): int
    {
        $appliedCount = 0;
        $processedProductIds = [];

        $conditions = MarkupConditionProxy::modelClass()::where('markup_group_id', $group->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($conditions as $condition) {
            $products = $this->resolveProductsForCondition($condition, $processedProductIds);

            foreach ($products as $product) {
                $cost = $this->getAttributeValue($product->id, 'cost');

                if ($cost === null || $cost <= 0) {
                    continue;
                }

                $adjustment = $this->calculateAdjustment($cost, $condition);
                $originalPrice = $this->getAttributeValue($product->id, 'price');
                $originalOldPrice = $this->getAttributeValue($product->id, 'old_price');
                $originalSpecialPrice = $this->getAttributeValue($product->id, 'special_price');

                if ($group->type === 'markup') {
                    $newPrice = round($cost + $adjustment, 4);

                    $this->setAttributeValue($product->id, 'price', $newPrice);
                    $this->setAttributeValue($product->id, 'old_price', $originalPrice);
                } else {
                    $newSpecialPrice = round(max(0, $cost - $adjustment), 4);

                    $this->setAttributeValue($product->id, 'price', $cost);
                    $this->setAttributeValue($product->id, 'special_price', $newSpecialPrice);
                }

                $this->appliedPriceRepository->create([
                    'markup_group_id'        => $group->id,
                    'product_id'             => $product->id,
                    'original_price'         => $originalPrice,
                    'original_old_price'     => $originalOldPrice,
                    'original_special_price' => $originalSpecialPrice,
                    'applied_price'          => $group->type === 'markup' ? ($cost + $adjustment) : $cost,
                    'applied_old_price'      => $group->type === 'markup' ? $originalPrice : null,
                    'applied_special_price'  => $group->type === 'discount' ? max(0, $cost - $adjustment) : null,
                ]);

                $processedProductIds[] = $product->id;
                $appliedCount++;
            }
        }

        $this->reindexProducts($processedProductIds);

        MarkupGroupProxy::modelClass()::where('id', $group->id)->update(['is_applied' => true]);

        $this->logRepository->create([
            'markup_group_id'   => $group->id,
            'action'            => 'applied',
            'products_affected' => $appliedCount,
            'message'           => "Applied {$group->type} \"{$group->name}\" to {$appliedCount} products.",
        ]);

        return $appliedCount;
    }

    /**
     * Revert markup/discount prices for a group.
     */
    public function revert(object $group): int
    {
        $appliedPrices = MarkupAppliedPriceProxy::modelClass()::where('markup_group_id', $group->id)->get();
        $productIds = [];

        foreach ($appliedPrices as $applied) {
            $this->setAttributeValue($applied->product_id, 'price', $applied->original_price);
            $this->setAttributeValue($applied->product_id, 'old_price', $applied->original_old_price);
            $this->setAttributeValue($applied->product_id, 'special_price', $applied->original_special_price);
            $productIds[] = $applied->product_id;
        }

        MarkupAppliedPriceProxy::modelClass()::where('markup_group_id', $group->id)->delete();

        $this->reindexProducts($productIds);

        MarkupGroupProxy::modelClass()::where('id', $group->id)->update(['is_applied' => false]);

        $revertedCount = count($productIds);

        $this->logRepository->create([
            'markup_group_id'   => $group->id,
            'action'            => 'reverted',
            'products_affected' => $revertedCount,
            'message'           => "Reverted {$group->type} \"{$group->name}\" for {$revertedCount} products.",
        ]);

        return $revertedCount;
    }

    /**
     * Find products matching a condition, excluding already-processed IDs.
     */
    protected function resolveProductsForCondition(object $condition, array $excludeProductIds): Collection
    {
        $productModel = ProductProxy::modelClass();
        $query = $productModel::query();

        $specificProductIds = $condition->products()->pluck('product_id')->toArray();

        if (! empty($specificProductIds)) {
            $query->whereIn('products.id', $specificProductIds);
        }

        $categoryIds = $condition->categories()->pluck('category_id')->toArray();

        if (! empty($categoryIds)) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($condition->cost_from !== null || $condition->cost_to !== null) {
            $query->whereHas('attribute_values', function ($q) use ($condition) {
                $q->where('attribute_id', self::COST_ATTRIBUTE_ID);

                if ($condition->cost_from !== null) {
                    $q->where('float_value', '>=', $condition->cost_from);
                }

                if ($condition->cost_to !== null) {
                    $q->where('float_value', '<=', $condition->cost_to);
                }
            });
        }

        if (! empty($excludeProductIds)) {
            $query->whereNotIn('products.id', $excludeProductIds);
        }

        return $query->get();
    }

    protected function calculateAdjustment(float $cost, object $condition): float
    {
        if ($condition->adjustment_type === 'percent') {
            return round($cost * ($condition->adjustment_value / 100), 4);
        }

        return (float) $condition->adjustment_value;
    }

    protected function getAttributeValue(int $productId, string $attributeCode): ?float
    {
        $attribute = $this->resolveAttribute($attributeCode);

        if (! $attribute) {
            return null;
        }

        return ProductAttributeValueProxy::modelClass()::where('product_id', $productId)
            ->where('attribute_id', $attribute->id)
            ->value('float_value');
    }

    protected function setAttributeValue(int $productId, string $attributeCode, ?float $value): void
    {
        $attribute = $this->resolveAttribute($attributeCode);

        if (! $attribute) {
            return;
        }

        $pavModel = ProductAttributeValueProxy::modelClass();

        $existing = $pavModel::where('product_id', $productId)
            ->where('attribute_id', $attribute->id)
            ->first();

        if ($existing) {
            $existing->update(['float_value' => $value]);
        } elseif ($value !== null) {
            $uniqueId = implode('|', array_filter([null, null, $productId, $attribute->id]));

            $pavModel::create([
                'product_id'   => $productId,
                'attribute_id' => $attribute->id,
                'float_value'  => $value,
                'unique_id'    => $uniqueId,
            ]);
        }
    }

    protected function resolveAttribute(string $code): ?object
    {
        if (isset($this->attributeCache[$code])) {
            return $this->attributeCache[$code];
        }

        $attribute = AttributeProxy::modelClass()::where('code', $code)->first();

        if ($attribute) {
            $this->attributeCache[$code] = $attribute;
        }

        return $attribute;
    }

    protected function reindexProducts(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $productModel = ProductProxy::modelClass();

        $products = $productModel::with([
            'variants',
            'attribute_values',
            'variants.attribute_values',
            'price_indices',
            'inventory_indices',
            'variants.price_indices',
            'variants.inventory_indices',
            'customer_group_prices',
            'variants.customer_group_prices',
            'catalog_rule_prices',
            'variants.catalog_rule_prices',
        ])->whereIn('id', $productIds)->get();

        app(PriceIndexer::class)->reindexBatch($products->all());
        app(FlatIndexer::class)->reindexBatch($products->all());
    }
}
