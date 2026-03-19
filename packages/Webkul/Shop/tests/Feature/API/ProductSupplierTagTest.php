<?php

use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\ProductTag\Models\Tag;
use Webkul\Supplier\Models\Supplier;

use function Pest\Laravel\getJson;

beforeEach(function () {
    config([
        'cache.default' => 'array',
        'responsecache.enabled' => false,
        'responsecache.cache_store' => 'array',
    ]);
});

it('returns supplier data in product api response', function () {
    // Arrange
    $supplier = Supplier::create([
        'name' => 'Test Supplier',
        'contact_name' => 'John Doe',
        'contact_email' => 'john@example.com',
        'contact_phone' => '+7-999-123-4567',
        'address' => '123 Test Street',
        'status' => true,
    ]);

    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    $product->newQuery()->where('id', $product->id)->update(['supplier_id' => $supplier->id]);

    // Act
    $response = getJson(route('shop.api.products.index'))
        ->assertOk();

    // Assert
    $item = collect($response->json('data'))->firstWhere('id', $product->id);

    expect($item)
        ->toHaveKey('supplier')
        ->and($item['supplier'])->toBe([
            'id' => $supplier->id,
            'name' => 'Test Supplier',
            'contact_name' => 'John Doe',
            'contact_email' => 'john@example.com',
            'contact_phone' => '+7-999-123-4567',
            'address' => '123 Test Street',
        ]);
});

it('returns null supplier when product has no supplier', function () {
    // Arrange
    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    // Act
    $response = getJson(route('shop.api.products.index'))
        ->assertOk();

    // Assert
    $item = collect($response->json('data'))->firstWhere('id', $product->id);

    expect($item)
        ->toHaveKey('supplier')
        ->and($item['supplier'])->toBeNull();
});

it('returns tags in product api response', function () {
    // Arrange
    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    $tagA = Tag::create(['name' => 'молоко', 'locale' => 'ru']);
    $tagB = Tag::create(['name' => 'dairy', 'locale' => 'ru']);

    $product->tags()->syncWithoutDetaching([$tagA->id, $tagB->id]);

    // Act
    $response = getJson(route('shop.api.products.index'))
        ->assertOk();

    // Assert
    $item = collect($response->json('data'))->firstWhere('id', $product->id);

    expect($item)
        ->toHaveKey('tags')
        ->and($item['tags'])->toHaveCount(2)
        ->and(collect($item['tags'])->pluck('name')->all())->toContain('молоко', 'dairy');
});

it('returns empty tags array when product has no tags', function () {
    // Arrange
    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    // Act
    $response = getJson(route('shop.api.products.index'))
        ->assertOk();

    // Assert
    $item = collect($response->json('data'))->firstWhere('id', $product->id);

    expect($item)
        ->toHaveKey('tags')
        ->and($item['tags'])->toBeArray()
        ->and($item['tags'])->toBeEmpty();
});
