<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Skip if attribute families haven't been seeded yet (e.g. migrate:fresh)
        if (! DB::table('attribute_families')->where('id', 1)->exists()) {
            return;
        }

        $now = Carbon::now();

        // 1. Shift settings(3→4) and inventories(4→5) groups to make room for product_data at position 3
        DB::table('attribute_groups')
            ->where('attribute_family_id', 1)
            ->where('column', 2)
            ->where('code', 'settings')
            ->update(['position' => 4]);

        DB::table('attribute_groups')
            ->where('attribute_family_id', 1)
            ->where('column', 2)
            ->where('code', 'inventories')
            ->update(['position' => 5]);

        // 2. Create "Product Data" attribute group
        DB::table('attribute_groups')->insert([
            'code' => 'product_data',
            'name' => 'Product Data',
            'column' => 2,
            'is_user_defined' => 0,
            'position' => 3,
            'attribute_family_id' => 1,
        ]);

        $productDataGroupId = DB::table('attribute_groups')
            ->where('code', 'product_data')
            ->where('attribute_family_id', 1)
            ->value('id');

        // 3. Insert new attributes
        $attributes = [
            [
                'code' => 'old_price',
                'admin_name' => 'Old Price',
                'type' => 'price',
                'validation' => 'decimal',
                'position' => 29,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'shelf_life',
                'admin_name' => 'Shelf Life',
                'type' => 'text',
                'validation' => null,
                'position' => 30,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'storage_temperature',
                'admin_name' => 'Storage Temperature',
                'type' => 'text',
                'validation' => null,
                'position' => 31,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'calories',
                'admin_name' => 'Calories',
                'type' => 'text',
                'validation' => 'decimal',
                'position' => 32,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'proteins',
                'admin_name' => 'Proteins',
                'type' => 'text',
                'validation' => 'decimal',
                'position' => 33,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'fats',
                'admin_name' => 'Fats',
                'type' => 'text',
                'validation' => 'decimal',
                'position' => 34,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'carbohydrates',
                'admin_name' => 'Carbohydrates',
                'type' => 'text',
                'validation' => 'decimal',
                'position' => 35,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'barcode',
                'admin_name' => 'Barcode',
                'type' => 'text',
                'validation' => null,
                'position' => 36,
                'is_required' => 0,
                'is_unique' => 1,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 0,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'supplier_code',
                'admin_name' => 'Supplier Code',
                'type' => 'text',
                'validation' => null,
                'position' => 37,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 0,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'published_at',
                'admin_name' => 'Published At',
                'type' => 'datetime',
                'validation' => null,
                'position' => 38,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 0,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 0,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
            [
                'code' => 'badge',
                'admin_name' => 'Badge',
                'type' => 'select',
                'validation' => null,
                'position' => 39,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'default_value' => null,
                'is_filterable' => 1,
                'is_configurable' => 0,
                'is_user_defined' => 1,
                'is_visible_on_front' => 1,
                'is_comparable' => 0,
                'enable_wysiwyg' => 0,
            ],
        ];

        foreach ($attributes as $attribute) {
            $attribute['created_at'] = $now;
            $attribute['updated_at'] = $now;
            DB::table('attributes')->insert($attribute);
        }

        // 4. Create badge attribute options
        $badgeAttributeId = DB::table('attributes')->where('code', 'badge')->value('id');

        $badgeOptions = [
            1 => ['en' => 'New Arrival', 'ru' => 'Новинка'],
            2 => ['en' => 'Hot Deal', 'ru' => 'Горячее предложение'],
            3 => ['en' => 'Sale', 'ru' => 'Распродажа'],
        ];

        foreach ($badgeOptions as $sortOrder => $labels) {
            DB::table('attribute_options')->insert([
                'admin_name' => $labels['en'],
                'sort_order' => $sortOrder,
                'attribute_id' => $badgeAttributeId,
            ]);

            $optionId = DB::getPdo()->lastInsertId();

            foreach ($labels as $locale => $label) {
                DB::table('attribute_option_translations')->insert([
                    'locale' => $locale,
                    'label' => $label,
                    'attribute_option_id' => $optionId,
                ]);
            }
        }

        // 5. Insert translations for all new attributes
        $translations = [
            'old_price' => ['en' => 'Old Price', 'ru' => 'Старая цена'],
            'shelf_life' => ['en' => 'Shelf Life', 'ru' => 'Срок годности'],
            'storage_temperature' => ['en' => 'Storage Temperature', 'ru' => 'Температурный режим'],
            'calories' => ['en' => 'Calories', 'ru' => 'Калории'],
            'proteins' => ['en' => 'Proteins', 'ru' => 'Белки'],
            'fats' => ['en' => 'Fats', 'ru' => 'Жиры'],
            'carbohydrates' => ['en' => 'Carbohydrates', 'ru' => 'Углеводы'],
            'barcode' => ['en' => 'Barcode', 'ru' => 'Штрих-код'],
            'supplier_code' => ['en' => 'Supplier Code', 'ru' => 'Код поставщика'],
            'published_at' => ['en' => 'Published At', 'ru' => 'Дата публикации'],
            'badge' => ['en' => 'Badge', 'ru' => 'Бейдж'],
        ];

        foreach ($translations as $code => $locales) {
            $attributeId = DB::table('attributes')->where('code', $code)->value('id');

            foreach ($locales as $locale => $name) {
                DB::table('attribute_translations')->insert([
                    'locale' => $locale,
                    'name' => $name,
                    'attribute_id' => $attributeId,
                ]);
            }
        }

        // 6. Map attributes to groups in the default family
        $priceGroupId = DB::table('attribute_groups')->where('code', 'price')->where('attribute_family_id', 1)->value('id');
        $generalGroupId = DB::table('attribute_groups')->where('code', 'general')->where('attribute_family_id', 1)->value('id');
        $settingsGroupId = DB::table('attribute_groups')->where('code', 'settings')->where('attribute_family_id', 1)->value('id');

        $groupMappings = [
            'old_price' => ['group_id' => $priceGroupId, 'position' => 6],
            'shelf_life' => ['group_id' => $productDataGroupId, 'position' => 1],
            'storage_temperature' => ['group_id' => $productDataGroupId, 'position' => 2],
            'calories' => ['group_id' => $productDataGroupId, 'position' => 3],
            'proteins' => ['group_id' => $productDataGroupId, 'position' => 4],
            'fats' => ['group_id' => $productDataGroupId, 'position' => 5],
            'carbohydrates' => ['group_id' => $productDataGroupId, 'position' => 6],
            'barcode' => ['group_id' => $generalGroupId, 'position' => 9],
            'supplier_code' => ['group_id' => $generalGroupId, 'position' => 10],
            'published_at' => ['group_id' => $settingsGroupId, 'position' => 6],
            'badge' => ['group_id' => $settingsGroupId, 'position' => 7],
        ];

        foreach ($groupMappings as $code => $mapping) {
            $attributeId = DB::table('attributes')->where('code', $code)->value('id');

            DB::table('attribute_group_mappings')->insert([
                'attribute_id' => $attributeId,
                'attribute_group_id' => $mapping['group_id'],
                'position' => $mapping['position'],
            ]);
        }
    }

    public function down(): void
    {
        $codes = [
            'old_price', 'shelf_life', 'storage_temperature',
            'calories', 'proteins', 'fats', 'carbohydrates',
            'barcode', 'supplier_code', 'published_at', 'badge',
        ];

        $attributeIds = DB::table('attributes')->whereIn('code', $codes)->pluck('id');

        DB::table('attribute_group_mappings')->whereIn('attribute_id', $attributeIds)->delete();
        DB::table('attribute_translations')->whereIn('attribute_id', $attributeIds)->delete();

        $badgeId = DB::table('attributes')->where('code', 'badge')->value('id');

        if ($badgeId) {
            $optionIds = DB::table('attribute_options')->where('attribute_id', $badgeId)->pluck('id');
            DB::table('attribute_option_translations')->whereIn('attribute_option_id', $optionIds)->delete();
            DB::table('attribute_options')->where('attribute_id', $badgeId)->delete();
        }

        DB::table('product_attribute_values')->whereIn('attribute_id', $attributeIds)->delete();
        DB::table('attributes')->whereIn('code', $codes)->delete();

        DB::table('attribute_groups')
            ->where('code', 'product_data')
            ->where('attribute_family_id', 1)
            ->delete();

        // Restore original positions
        DB::table('attribute_groups')
            ->where('attribute_family_id', 1)
            ->where('column', 2)
            ->where('code', 'settings')
            ->update(['position' => 3]);

        DB::table('attribute_groups')
            ->where('attribute_family_id', 1)
            ->where('column', 2)
            ->where('code', 'inventories')
            ->update(['position' => 4]);
    }
};
