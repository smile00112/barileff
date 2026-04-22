<?php

use Illuminate\Support\Str;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryTranslation;

use function Pest\Laravel\get;

it('renders active child categories on the category page', function () {
    $parentCategory = Category::factory()->create([
        'status' => 1,
        'parent_id' => 1,
    ]);

    $childCategory = Category::factory()->create([
        'status' => 1,
        'parent_id' => $parentCategory->id,
    ]);

    $hiddenChildCategory = Category::factory()->create([
        'status' => 0,
        'parent_id' => $parentCategory->id,
    ]);

    $parentSlug = 'category-page-'.Str::lower((string) Str::ulid());
    $childSlug = 'child-category-'.Str::lower((string) Str::ulid());
    $hiddenChildSlug = 'hidden-child-category-'.Str::lower((string) Str::ulid());

    CategoryTranslation::query()->create([
        'category_id' => $parentCategory->id,
        'locale' => 'en',
        'locale_id' => 1,
        'name' => 'Parent Category',
        'slug' => $parentSlug,
        'description' => 'Parent category description',
    ]);

    CategoryTranslation::query()->create([
        'category_id' => $childCategory->id,
        'locale' => 'en',
        'locale_id' => 1,
        'name' => 'Visible Child Category',
        'slug' => $childSlug,
        'description' => 'Visible child category description',
    ]);

    CategoryTranslation::query()->create([
        'category_id' => $hiddenChildCategory->id,
        'locale' => 'en',
        'locale_id' => 1,
        'name' => 'Hidden Child Category',
        'slug' => $hiddenChildSlug,
        'description' => 'Hidden child category description',
    ]);

    $response = get('/'.$parentSlug);

    $response->assertOk()
        ->assertViewHas('category', function ($category) use ($childCategory, $hiddenChildCategory) {
            return $category->relationLoaded('children')
                && $category->children->pluck('id')->contains($childCategory->id)
                && ! $category->children->pluck('id')->contains($hiddenChildCategory->id);
        })
        ->assertSeeText('Visible Child Category')
        ->assertDontSeeText('Hidden Child Category');
});
