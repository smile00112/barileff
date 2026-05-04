<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\CategoryTranslation;
use Webkul\Faker\Helpers\Category as CategoryFaker;
use Webkul\Shop\Http\Resources\CategoryResource;
use Webkul\Shop\Http\Resources\CategoryTreeResource;

use function Pest\Laravel\get;

it('decodes html entities in category name for shop category resource', function () {
    $category = (new CategoryFaker)->factory()->create();

    $translationLocale = CategoryTranslation::query()
        ->where('category_id', $category->id)
        ->value('locale');

    expect($translationLocale)->not->toBeNull();

    CategoryTranslation::query()
        ->where('category_id', $category->id)
        ->update(['name' => 'Крафт &amp; Импорт']);

    App::setLocale($translationLocale);

    $payload = (new CategoryResource($category->fresh()))->toArray(request());

    expect($payload['name'])->toBe('Крафт & Импорт');
});

it('exposes decoded additional payload on category tree resource', function () {
    get(route('shop.home.index'))->assertOk();

    $category = (new CategoryFaker)->factory()->create();

    $updatedRows = DB::table('categories')
        ->where('id', $category->id)
        ->update(['additional' => json_encode(['type' => 'decoration'])]);

    expect($updatedRows)->toBe(1);

    $translationLocale = CategoryTranslation::query()
        ->where('category_id', $category->id)
        ->value('locale');

    expect($translationLocale)->not->toBeNull();

    App::setLocale($translationLocale);

    $treeCategory = $category->fresh();

    expect($treeCategory)->not->toBeNull();

    $treeCategory->setRelation('children', collect());

    $payload = (new CategoryTreeResource($treeCategory))->toArray(request());

    expect($payload['additional'])->toBe(['type' => 'decoration']);
});
