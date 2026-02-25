<?php

use Webkul\Inventory\Models\InventorySource;
use Webkul\Shipping\Models\DeliveryCity;
use Webkul\Shipping\Models\DeliveryZone;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

it('should render delivery city and zone index pages', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.delivery_cities.index'))
        ->assertOk()
        ->assertSeeText('Delivery Cities');

    get(route('admin.settings.delivery_zones.index'))
        ->assertOk()
        ->assertSeeText('Delivery Zones');
});

it('should create delivery city and delivery zone', function () {
    $this->loginAsAdmin();

    $inventorySource = InventorySource::factory()->create();

    postJson(route('admin.settings.delivery_cities.store'), [
        'code' => 'moscow',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => 1,
    ])->assertRedirect(route('admin.settings.delivery_cities.index'));

    $city = DeliveryCity::query()->where('code', 'moscow')->firstOrFail();

    postJson(route('admin.settings.delivery_zones.store'), [
        'city_id' => $city->id,
        'code' => 'center',
        'name' => 'Center Zone',
        'polygon_json' => json_encode([
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ]),
        'delivery_time_minutes' => 60,
        'is_active' => 1,
        'inventory_source_ids' => [$inventorySource->id],
        'rates' => [
            ['min_order_total' => 0, 'price' => 300, 'sort_order' => 0],
        ],
    ])->assertRedirect(route('admin.settings.delivery_zones.index'));

    $zone = DeliveryZone::query()->where('code', 'center')->firstOrFail();

    expect($zone->name)->toBe('Center Zone')
        ->and($zone->rates()->count())->toBe(1)
        ->and($zone->inventory_sources()->count())->toBe(1);
});

it('should append yandex maps api key to map script url on create and edit pages', function () {
    $this->loginAsAdmin();

    config()->set('services.yandex_maps.api_key', 'test-yandex-key');

    $city = DeliveryCity::query()->create([
        'code' => 'spb',
        'name' => 'Saint Petersburg',
        'country' => 'RU',
        'state' => 'SPE',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'spb-center',
        'name' => 'SPB Center',
        'polygon_json' => [],
        'is_active' => true,
    ]);

    get(route('admin.settings.delivery_zones.create'))
        ->assertOk()
        ->assertSee('api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=test-yandex-key', false)
        ->assertSee('const initDeliveryZoneForm = () => {', false)
        ->assertSee('const syncPolygonInput = (value) => {', false)
        ->assertSee('window.setTimeout(initDeliveryZoneForm, 0);', false);

    get(route('admin.settings.delivery_zones.edit', $zone->id))
        ->assertOk()
        ->assertSee('api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=test-yandex-key', false)
        ->assertSee('const initDeliveryZoneForm = () => {', false)
        ->assertSee('const syncPolygonInput = (value) => {', false)
        ->assertSee('window.setTimeout(initDeliveryZoneForm, 0);', false);
});
