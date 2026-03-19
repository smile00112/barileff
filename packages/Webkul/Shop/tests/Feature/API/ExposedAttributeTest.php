<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Faker\Helpers\Product as ProductFaker;

use function Pest\Laravel\getJson;

it('exposes configured text attributes as top-level fields in shop api', function () {
    // Arrange — use the existing "product_number" (text) attribute.
    $attribute = Attribute::where('code', 'product_number')->firstOrFail();

    config(['products.api_exposed_attributes' => ['product_number']]);

    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    $expectedValue = $product->product_number;

    // Act
    $response = getJson(route('shop.api.products.index'))
        ->assertOk();

    // Assert
    $item = collect($response->json('data'))->firstWhere('id', $product->id);

    expect($item)
        ->toHaveKey('product_number')
        ->and($item['product_number'])->toBe($expectedValue);
});

it('does not expose attributes that are not configured', function () {
    // Arrange — keep config empty.
    config(['products.api_exposed_attributes' => []]);

    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    // Act
    $response = getJson(route('shop.api.products.index'))
        ->assertOk();

    // Assert
    $item = collect($response->json('data'))->firstWhere('id', $product->id);

    expect($item)->not->toHaveKey('product_number');
});

it('exposes select attribute labels instead of ids', function () {
    // Arrange — create a custom select attribute with options.
    $attribute = Attribute::create([
        'code' => 'test_brand_'.now()->timestamp,
        'admin_name' => 'Test Brand',
        'type' => 'select',
        'is_required' => false,
        'is_unique' => false,
        'is_filterable' => false,
        'is_configurable' => false,
        'is_visible_on_front' => false,
        'is_user_defined' => true,
    ]);

    $option = $attribute->options()->create([
        'admin_name' => 'BrandX',
        'sort_order' => 1,
    ]);

    $option->translations()->create([
        'locale' => app()->getLocale(),
        'label' => 'BrandX',
    ]);

    // Attach attribute to default family.
    $family = \Webkul\Attribute\Models\AttributeFamily::first();
    $group = $family->attribute_groups()->first();
    $group->custom_attributes()->attach($attribute->id);

    config(['products.api_exposed_attributes' => [$attribute->code]]);

    $product = (new ProductFaker([
        'attributes' => [
            $attribute->id => $attribute->code,
        ],
        'attribute_value' => [
            $attribute->code => [
                'integer_value' => $option->id,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    // Act
    $response = getJson(route('shop.api.products.index'))
        ->assertOk();

    // Assert
    $item = collect($response->json('data'))->firstWhere('id', $product->id);

    expect($item)
        ->toHaveKey($attribute->code)
        ->and($item[$attribute->code])->toBe('BrandX');
});
