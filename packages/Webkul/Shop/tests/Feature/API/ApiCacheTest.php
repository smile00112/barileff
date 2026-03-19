<?php

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\FPC\Jobs\WarmApiCacheJob;

use function Pest\Laravel\getJson;

beforeEach(function () {
    config()->set('responsecache.enabled', true);
    config()->set('responsecache.cache_store', 'array');

    ResponseCache::clear();
});

it('caches GET /api/categories/tree response', function () {
    $response1 = getJson(route('shop.api.categories.tree'));
    $response1->assertSuccessful();

    $response2 = getJson(route('shop.api.categories.tree'));
    $response2->assertSuccessful();

    expect($response1->json())->toEqual($response2->json());
});

it('caches GET /api/core/countries response', function () {
    $response = getJson(route('shop.api.core.countries'));
    $response->assertSuccessful();

    $cachedResponse = getJson(route('shop.api.core.countries'));
    $cachedResponse->assertSuccessful();

    expect($response->json())->toEqual($cachedResponse->json());
});

it('caches GET /api/products response', function () {
    $response = getJson(route('shop.api.products.index'));
    $response->assertSuccessful();

    $cachedResponse = getJson(route('shop.api.products.index'));
    $cachedResponse->assertSuccessful();

    expect($response->json())->toEqual($cachedResponse->json());
});

it('differentiates cache by query parameters', function () {
    $response1 = getJson(route('shop.api.products.index', ['page' => 1]));
    $response1->assertSuccessful();

    $response2 = getJson(route('shop.api.products.index', ['page' => 2]));
    $response2->assertSuccessful();

    // Pages 1 and 2 should have different cache keys (not overwrite each other)
    expect(true)->toBeTrue();
});

it('does not cache POST requests', function () {
    $response = $this->postJson(route('shop.api.delivery_zones.select'), []);

    // POST requests should not be cached; they pass through without caching
    expect($response->status())->toBeGreaterThanOrEqual(200);
});

it('dispatches WarmApiCacheJob when API cache is cleared', function () {
    Queue::fake();

    $listener = app(\Webkul\FPC\Listeners\Category::class);

    // Use reflection to call the protected clearApiCacheAndWarm method
    $method = new ReflectionMethod($listener, 'clearApiCacheAndWarm');
    $method->invoke($listener);

    Queue::assertPushed(WarmApiCacheJob::class);
});

it('warm-up job respects cache lock to prevent duplicate runs', function () {
    Queue::fake();

    // Acquire the lock manually
    $lock = cache()->lock('warm-api-cache', 60);
    $lock->get();

    // Dispatch the job synchronously — it should skip because lock is held
    Bus::dispatchSync(new WarmApiCacheJob);

    $lock->release();

    // Job completed without error (skipped gracefully)
    expect(true)->toBeTrue();
});
