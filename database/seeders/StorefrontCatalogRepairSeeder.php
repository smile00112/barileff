<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\ProductTableSeeder;

class StorefrontCatalogRepairSeeder extends Seeder
{
    /**
     * Repair demo storefront catalog data for the active locale.
     */
    public function run(): void
    {
        $defaultLocale = config('app.locale');
        $productSeeder = new ProductTableSeeder;
        $sampleProducts = $productSeeder->prepareProductsData([$defaultLocale])[$defaultLocale] ?? [];
        $attributes = DB::table('attributes')->get()->keyBy('code');
        $attributeTypeFields = $productSeeder->attributeTypeFields;
        $skipAttributes = ['product_id', 'parent_id', 'type', 'attribute_family_id', 'locale', 'channel', 'created_at', 'updated_at'];

        foreach ($sampleProducts as $productData) {
            $productId = DB::table('products')
                ->where('sku', $productData['sku'])
                ->value('id');

            if (! $productId) {
                continue;
            }

            DB::table('product_flat')
                ->where('product_id', $productId)
                ->where('channel', $productData['channel'])
                ->where('locale', $productData['locale'])
                ->update(Arr::only($productData, [
                    'price',
                    'special_price',
                    'special_price_from',
                    'special_price_to',
                    'weight',
                    'status',
                    'visible_individually',
                    'new',
                    'featured',
                ]));

            foreach ($productData as $attributeCode => $value) {
                if (in_array($attributeCode, $skipAttributes, true)) {
                    continue;
                }

                $attribute = $attributes->get($attributeCode);

                if (! $attribute || ! isset($attributeTypeFields[$attribute->type])) {
                    continue;
                }

                $channel = $attribute->value_per_channel ? 'default' : null;
                $locale = $attribute->value_per_locale ? $defaultLocale : null;
                $typeColumns = array_fill_keys(array_values($attributeTypeFields), null);
                $valuePayload = array_merge($typeColumns, [
                    'attribute_id' => $attribute->id,
                    'product_id' => $productId,
                    $attributeTypeFields[$attribute->type] => $value,
                    'channel' => $channel,
                    'locale' => $locale,
                    'unique_id' => implode('|', array_filter([
                        $channel,
                        $locale,
                        $productId,
                        $attribute->id,
                    ])),
                    'json_value' => null,
                ]);

                $existingValue = DB::table('product_attribute_values')
                    ->where('attribute_id', $attribute->id)
                    ->where('product_id', $productId)
                    ->when($channel === null, fn ($query) => $query->whereNull('channel'), fn ($query) => $query->where('channel', $channel))
                    ->when($locale === null, fn ($query) => $query->whereNull('locale'), fn ($query) => $query->where('locale', $locale))
                    ->first();

                if ($existingValue) {
                    DB::table('product_attribute_values')
                        ->where('id', $existingValue->id)
                        ->update($valuePayload);

                    continue;
                }

                DB::table('product_attribute_values')->insert($valuePayload);
            }
        }

        DB::table('category_translations')
            ->where('category_id', 2)
            ->where('locale', 'ru')
            ->update([
                'url_path' => 'Мужчины',
            ]);

        DB::table('category_translations')
            ->where('category_id', 3)
            ->where('locale', 'ru')
            ->update([
                'slug' => 'зимняя-одежда',
                'url_path' => 'Мужчины/зимняя-одежда',
            ]);
    }
}
