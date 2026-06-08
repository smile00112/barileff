<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Category\Services\CategoryMenuCacheService;
use Webkul\Core\Core;

it('builds unique cache keys for different inventory sources', function () {
    $repository = Mockery::mock(CategoryRepository::class);
    $core = Mockery::mock(Core::class);

    $service = new CategoryMenuCacheService($repository, $core);

    expect($service->cacheKey('default', 'en', 1))
        ->not->toBe($service->cacheKey('default', 'en', 2))
        ->and($service->cacheKey('default', 'en', null))
        ->toBe('category-menu-tree:default:en:0');
});

it('caches category trees independently per inventory source', function () {
    config()->set('cache.default', 'array');
    Cache::flush();

    $repository = Mockery::mock(CategoryRepository::class);
    $core = Mockery::mock(Core::class);

    $service = new CategoryMenuCacheService($repository, $core);

    $core->shouldReceive('getConfigData')
        ->with('catalog.products.settings.filter_categories_by_stock')
        ->andReturn(true);

    $buildTree = function (): Collection {
        $first = new Category;
        $second = new Category;

        $first->setAttribute('id', 1);
        $second->setAttribute('id', 2);
        $first->setRelation('children', collect());
        $second->setRelation('children', collect());

        return collect([$first, $second]);
    };

    $repository->shouldReceive('getVisibleCategoryTree')
        ->with(10)
        ->twice()
        ->andReturnUsing($buildTree);

    $repository->shouldReceive('getCategoryIdsWithStockForSource')
        ->with(101)
        ->once()
        ->andReturn([1]);

    $repository->shouldReceive('getCategoryIdsWithStockForSource')
        ->with(202)
        ->once()
        ->andReturn([2]);

    $source101FirstCall = $service->get(10, 101, 'default', 'en');
    $source101SecondCall = $service->get(10, 101, 'default', 'en');
    $source202Call = $service->get(10, 202, 'default', 'en');

    expect($source101FirstCall->pluck('id')->all())
        ->toBe([1])
        ->and($source101SecondCall->pluck('id')->all())
        ->toBe([1])
        ->and($source202Call->pluck('id')->all())
        ->toBe([2]);
});

it('serves category menu cache with stale while revalidate', function () {
    $repository = Mockery::mock(CategoryRepository::class);
    $core = Mockery::mock(Core::class);

    $service = new CategoryMenuCacheService($repository, $core);

    $core->shouldReceive('getConfigData')
        ->once()
        ->with('catalog.products.settings.filter_categories_by_stock')
        ->andReturn(true);

    Cache::shouldReceive('flexible')
        ->once()
        ->withArgs(function (string $key, array $ttl, Closure $callback): bool {
            expect($key)->toBe('category-menu-tree:default:en:0')
                ->and($ttl)->toBe([86400, 172800]);

            return true;
        })
        ->andReturnUsing(function (string $key, array $ttl, Closure $callback, ?array $lock = null): Collection {
            expect($lock)->toBe(['seconds' => 60]);

            return $callback();
        });

    $category = new Category;
    $category->setAttribute('id', 1);
    $category->setRelation('children', collect());

    $repository->shouldReceive('getVisibleCategoryTree')
        ->once()
        ->with(10)
        ->andReturn(collect([$category]));

    $service->get(10, null, 'default', 'en');
});
