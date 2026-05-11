<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Faker\Helpers\Category as CategoryFaker;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Product\Models\ProductInventory;

use function Pest\Laravel\getJson;

it('includes only categories with stock when inventory_source_id is given', function () {
    $source = InventorySource::factory()->create();

    $categoryWithStock = (new CategoryFaker)->factory()->create();
    $categoryEmpty = (new CategoryFaker)->factory()->create();

    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->hasAttached($categoryWithStock)
        ->create();

    ProductInventory::factory()->create([
        'product_id' => $product->id,
        'inventory_source_id' => $source->id,
        'qty' => 10,
    ]);

    $response = getJson(route('shop.api.categories.index', [
        'status' => 1,
        'locale' => 'en',
        'parent_id' => (string) $categoryWithStock->parent_id,
        'inventory_source_id' => $source->id,
        'limit' => 100,
    ]))->assertOk();

    $returnedIds = collect($response->json('data'))->pluck('id');

    expect($returnedIds)->toContain($categoryWithStock->id)
        ->and($returnedIds)->not->toContain($categoryEmpty->id);
});

it('returns all matching categories when inventory_source_id is absent', function () {
    $categoryA = (new CategoryFaker)->factory()->create();
    $categoryB = (new CategoryFaker)->factory()->create();

    $response = getJson(route('shop.api.categories.index', [
        'status' => 1,
        'locale' => 'en',
        'parent_id' => (string) $categoryA->parent_id,
        'limit' => 100,
    ]))->assertOk();

    $returnedIds = collect($response->json('data'))->pluck('id');

    expect($returnedIds)->toContain($categoryA->id)
        ->and($returnedIds)->toContain($categoryB->id);
});

it('returns empty result when inventory_source_id has no stock in any category', function () {
    $source = InventorySource::factory()->create();

    $category = (new CategoryFaker)->factory()->create();

    // No ProductInventory row for $source → category must be excluded.

    $response = getJson(route('shop.api.categories.index', [
        'status' => 1,
        'locale' => 'en',
        'parent_id' => (string) $category->parent_id,
        'inventory_source_id' => $source->id,
        'limit' => 100,
    ]))->assertOk();

    $returnedIds = collect($response->json('data'))->pluck('id');

    expect($returnedIds)->not->toContain($category->id);
});

it('ignores inventory_source_id and returns all categories when filter_categories_by_stock config is disabled', function () {
    CoreConfig::factory()->create([
        'code' => 'catalog.products.settings.filter_categories_by_stock',
        'value' => '0',
    ]);

    $source = InventorySource::factory()->create();

    $categoryWithStock = (new CategoryFaker)->factory()->create();
    $categoryEmpty = (new CategoryFaker)->factory()->create();

    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->hasAttached($categoryWithStock)
        ->create();

    ProductInventory::factory()->create([
        'product_id' => $product->id,
        'inventory_source_id' => $source->id,
        'qty' => 10,
    ]);

    $response = getJson(route('shop.api.categories.index', [
        'status' => 1,
        'locale' => 'en',
        'parent_id' => (string) $categoryWithStock->parent_id,
        'inventory_source_id' => $source->id,
        'limit' => 100,
    ]))->assertOk();

    $returnedIds = collect($response->json('data'))->pluck('id');

    // Both categories must appear because filtering is disabled.
    expect($returnedIds)->toContain($categoryWithStock->id)
        ->and($returnedIds)->toContain($categoryEmpty->id);
});
