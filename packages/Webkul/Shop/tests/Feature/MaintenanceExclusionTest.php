<?php

use Webkul\CMS\Models\Page;
use Webkul\Core\Models\Channel;

use function Pest\Laravel\get;

beforeEach(function () {
    $channel = Channel::query()->first();
    $channel->update([
        'is_maintenance_on' => true,
        'maintenance_excluded_paths' => 'api/*,page/*',
    ]);
    app()->maintenanceMode()->activate([]);
});

afterEach(function () {
    app()->maintenanceMode()->deactivate();
    Channel::query()->update(['is_maintenance_on' => false]);
});

it('allows api routes when maintenance is on and paths are excluded', function () {
    get(route('shop.api.core.countries'))
        ->assertOk();
});

it('allows cms page routes when maintenance is on and paths are excluded', function () {
    $page = Page::query()->whereHas('translations', fn ($q) => $q->where('url_key', 'about-us'))->first();

    if (! $page) {
        $page = Page::factory()->hasTranslations()->create();
    }

    get(route('shop.cms.page', $page->url_key))
        ->assertSuccessful();
});

it('returns 503 for home page when maintenance is on', function () {
    get(route('shop.home.index'))
        ->assertStatus(503);
});
