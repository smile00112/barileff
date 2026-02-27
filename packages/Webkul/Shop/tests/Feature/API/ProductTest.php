<?php

use Pest\Expectation;
use Webkul\Faker\Helpers\Product as ProductFaker;

use function Pest\Laravel\getJson;

it('returns a new products listing', function () {
    // Arrange.
    $newProductOptions = [
        'attributes' => [
            5 => 'new',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
        ],
    ];

    (new ProductFaker($newProductOptions))
        ->getSimpleProductFactory()
        ->create();

    // Act
    $response = getJson(route('shop.api.products.index', ['new' => 1]))
        ->assertOk()
        ->collect();

    // Assert
    expect($response['data'])->each(function (Expectation $product) {
        return $product->is_new->toBeTrue();
    });
});

it('returns a featured products listing', function () {
    // Arrange.
    $featuredProductOptions = [
        'attributes' => [
            6 => 'featured',
        ],

        'attribute_value' => [
            'featured' => [
                'boolean_value' => true,
            ],
        ],
    ];

    (new ProductFaker($featuredProductOptions))
        ->getSimpleProductFactory()
        ->create();

    // Act
    $response = getJson(route('shop.api.products.index', ['featured' => 1]))
        ->assertOk()
        ->collect();

    // Assert
    expect($response['data'])->each(function (Expectation $product) {
        return $product->is_featured->toBeTrue();
    });
});

it('returns all products listing', function () {
    // Arrange.
    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    // Act and Assert.
    getJson(route('shop.api.products.index'))
        ->assertOk()
        ->assertJsonIsArray('data')
        ->assertJsonFragment([
            'id' => $product->id,
        ]);
});

it('returns related products for a valid numeric product id', function () {
    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    getJson(route('shop.api.products.related.index', ['id' => $product->id]))
        ->assertOk()
        ->assertJsonIsArray('data');
});

it('returns up-sell products for a valid numeric product id', function () {
    $product = (new ProductFaker)
        ->getSimpleProductFactory()
        ->create();

    getJson(route('shop.api.products.up-sell.index', ['id' => $product->id]))
        ->assertOk()
        ->assertJsonIsArray('data');
});

it('returns not found for non-numeric related product id', function () {
    getJson(route('shop.api.products.related.index', ['id' => 'architecto']))
        ->assertNotFound();
});

it('returns not found for non-numeric up-sell product id', function () {
    getJson(route('shop.api.products.up-sell.index', ['id' => 'architecto']))
        ->assertNotFound();
});
