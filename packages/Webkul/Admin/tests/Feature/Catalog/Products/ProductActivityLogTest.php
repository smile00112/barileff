<?php

use Spatie\Activitylog\LogOptions;
use Webkul\Product\Models\Product;

it('getActivitylogOptions tracks only the configured fields', function () {
    $product = new Product;
    $options = $product->getActivitylogOptions();

    expect($options)->toBeInstanceOf(LogOptions::class);
    expect($options->logAttributes)->toBe(['sku', 'type', 'attribute_family_id', 'supplier_id']);
    expect($options->submitEmptyLogs)->toBeFalse();
    expect($options->logOnlyDirty)->toBeTrue();
});
