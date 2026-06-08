<?php

use Webkul\Category\Models\Category;
use Webkul\Shop\Http\Resources\CategoryTreeResource;

it('loads nested grid sections from the cached category tree endpoint', function () {
    $source = file_get_contents(base_path('packages/Webkul/Shop/src/Resources/views/components/categories/nested-grid.blade.php'));

    expect($source)
        ->toContain("route('shop.api.categories.tree')")
        ->not->toContain("route('shop.api.categories.index')");
});

it('includes category logos in tree resources for nested grid cards', function () {
    $category = new Category;
    $category->setAttribute('id', 1);
    $category->setAttribute('logo_path', 'category/1/logo.webp');
    $category->setRelation('children', collect());

    $payload = (new CategoryTreeResource($category))->toArray(request());

    expect($payload['logo'])
        ->toBeArray()
        ->and($payload['logo']['large_image_url'])
        ->toBe(url('cache/large/category/1/logo.webp'));
});
