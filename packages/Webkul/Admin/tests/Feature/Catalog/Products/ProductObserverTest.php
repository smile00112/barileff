<?php

use Webkul\Product\Models\Product;
use Webkul\Product\Observers\ProductObserver;

it('auto-generates SKU in the creating observer when none is provided', function () {
    $observer = new ProductObserver;
    $product = new Product;

    $observer->creating($product);

    expect($product->sku)->toMatch('/^ART-\d{6}$/');
});

it('does not overwrite an existing SKU in the creating observer', function () {
    $observer = new ProductObserver;
    $product = new Product;
    $product->sku = 'MY-CUSTOM-SKU';

    $observer->creating($product);

    expect($product->sku)->toBe('MY-CUSTOM-SKU');
});
