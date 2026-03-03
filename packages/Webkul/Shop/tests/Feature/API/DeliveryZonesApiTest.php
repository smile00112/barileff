<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Shipping\Models\DeliveryCity;
use Webkul\Shipping\Models\DeliveryZone;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    CoreConfig::factory()->create([
        'code' => 'sales.carriers.delivery_zones.active',
        'value' => 1,
    ]);
    $this->inventorySource = InventorySource::factory()->create();
    core()->getCurrentChannel()->inventory_sources()->sync([$this->inventorySource->id]);

    $this->city = DeliveryCity::query()->create([
        'code' => 'test-city',
        'name' => 'Test City',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
        'center_lat' => 55.75,
        'center_lng' => 37.62,
    ]);

    $this->zone = DeliveryZone::query()->create([
        'city_id' => $this->city->id,
        'code' => 'test-zone',
        'name' => 'Test Zone',
        'polygon_json' => [
            [55.76, 37.60],
            [55.76, 37.70],
            [55.70, 37.70],
            [55.70, 37.60],
        ],
        'polygon_color' => '#0077cc',
        'is_active' => true,
    ]);
    $this->zone->inventory_sources()->sync([$this->inventorySource->id]);
});

it('returns delivery zones for map', function () {
    $response = getJson(route('shop.api.delivery_zones.index'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'center_lat',
                    'center_lng',
                    'zones' => [
                        [
                            'id',
                            'name',
                            'polygon_json',
                            'polygon_color',
                            'inventory_source_id',
                        ],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.0.name', 'Test City')
        ->assertJsonPath('data.0.zones.0.name', 'Test Zone')
        ->assertJsonPath('data.0.zones.0.inventory_source_id', $this->inventorySource->id);
});

it('selects zone and sets session when no cart', function () {
    $response = postJson(route('shop.api.delivery_zones.select'), [
        'delivery_zone_id' => $this->zone->id,
        '_token' => csrf_token(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.inventory_source_id', $this->inventorySource->id)
        ->assertJsonPath('data.zone.id', $this->zone->id)
        ->assertJsonPath('data.zone.name', 'Test Zone');

    expect((int) session('selected_inventory_source_id'))->toBe($this->inventorySource->id);
});

it('returns 422 when zone not found for select', function () {
    $inactiveZone = DeliveryZone::query()->create([
        'city_id' => $this->city->id,
        'code' => 'inactive',
        'name' => 'Inactive',
        'polygon_json' => [[55.0, 37.0], [55.0, 38.0], [54.0, 38.0]],
        'is_active' => false,
    ]);

    $response = postJson(route('shop.api.delivery_zones.select'), [
        'delivery_zone_id' => $inactiveZone->id,
        '_token' => csrf_token(),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('data.zone', null);
});
