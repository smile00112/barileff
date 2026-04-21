<?php

use Illuminate\Support\Facades\DB;
use Webkul\Category\Repositories\CategoryRepository;

it('creates nested category chain under anchor and resolves the same ids', function () {
    $repo = app(CategoryRepository::class);
    $anchorId = (int) core()->getDefaultChannel()->root_category_id;
    $suffix = str_replace('.', '_', uniqid('chain_', true));
    $segments = ["ImportCatA{$suffix}", "ImportCatB{$suffix}"];

    $ids = $repo->ensureCategoryChainUnderParent($anchorId, $segments, 'en');

    expect($ids)->toHaveCount(2);

    $catA = $repo->find($ids[0]);
    $catB = $repo->find($ids[1]);

    expect((int) $catA->parent_id)->toBe($anchorId)
        ->and((int) $catB->parent_id)->toBe($ids[0]);

    $resolved = $repo->resolveCategoryChainUnderParent($anchorId, $segments, 'en');

    expect($resolved)->toBe($ids);
});

it('attaches price filter to newly created categories', function () {
    $repo = app(CategoryRepository::class);
    $anchorId = (int) core()->getDefaultChannel()->root_category_id;
    $suffix = str_replace('.', '_', uniqid('price_filter_', true));
    $segments = ["PriceFilterCat{$suffix}"];

    $ids = $repo->ensureCategoryChainUnderParent($anchorId, $segments, 'en');

    $category = $repo->find($ids[0]);
    $priceAttributeId = DB::table('attributes')->where('code', 'price')->value('id');

    expect($priceAttributeId)->not->toBeNull();

    $filterableIds = $category->filterableAttributes()->pluck('attributes.id')->toArray();

    expect($filterableIds)->toContain((int) $priceAttributeId);
});

it('returns empty array when chain cannot be fully resolved', function () {
    $repo = app(CategoryRepository::class);
    $anchorId = (int) core()->getDefaultChannel()->root_category_id;
    $missing = 'TotallyMissingCategory'.str_replace('.', '_', uniqid('_', true));

    expect($repo->resolveCategoryChainUnderParent($anchorId, [$missing], 'en'))->toBe([]);
});
