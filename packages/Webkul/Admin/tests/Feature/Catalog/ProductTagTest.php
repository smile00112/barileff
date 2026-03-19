<?php

use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\ProductTag\Models\Tag;
use Webkul\ProductTag\Repositories\TagRepository;
use Webkul\ProductTag\Services\GigaChatTagService;

use function Pest\Laravel\mock;

it('syncByNames creates new tags and returns their IDs', function () {
    $repository = app(TagRepository::class);

    $ids = $repository->syncByNames(['молоко', 'dairy', 'молочные продукты'], 'ru');

    expect($ids)->toHaveCount(3);

    $this->assertDatabaseHas('tags', ['name' => 'молоко', 'locale' => 'ru']);
    $this->assertDatabaseHas('tags', ['name' => 'dairy', 'locale' => 'ru']);
});

it('syncByNames returns existing tag ID without creating duplicates', function () {
    $tag = Tag::create(['name' => 'кефир', 'locale' => 'ru']);

    $repository = app(TagRepository::class);
    $ids = $repository->syncByNames(['кефир'], 'ru');

    expect($ids)->toBe([$tag->id]);
    expect(Tag::where('name', 'кефир')->count())->toBe(1);
});

it('generates tags using GigaChatTagService and sync them to product', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    mock(GigaChatTagService::class)
        ->shouldReceive('generateTags')
        ->once()
        ->andReturn(['молоко', 'кефир', 'dairy']);

    $service = app(GigaChatTagService::class);
    $names = $service->generateTags($product);

    $tagRepository = app(TagRepository::class);
    $tagIds = $tagRepository->syncByNames($names);
    $product->tags()->syncWithoutDetaching($tagIds);

    expect($product->tags()->count())->toBe(3);

    $this->assertDatabaseHas('product_tag', ['product_id' => $product->id]);
});

it('GigaChatTagService::parseResponse strips empty and over-long values', function () {
    $service = new GigaChatTagService;

    $reflection = new ReflectionMethod($service, 'parseResponse');
    $result = $reflection->invoke($service, '  молоко , , '.str_repeat('x', 200), 10);

    expect($result)->not->toContain('');
    expect($result)->not->toContain(str_repeat('x', 200));
});
