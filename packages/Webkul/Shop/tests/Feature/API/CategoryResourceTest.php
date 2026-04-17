<?php

use Illuminate\Support\Facades\App;
use Webkul\Category\Models\CategoryTranslation;
use Webkul\Faker\Helpers\Category as CategoryFaker;
use Webkul\Shop\Http\Resources\CategoryResource;

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
