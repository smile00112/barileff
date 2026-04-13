<?php

use Webkul\Inventory\Models\InventorySource;
use Webkul\Shipping\Models\DeliveryCity;
use Webkul\Shipping\Models\DeliveryZone;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

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

    post(route('admin.settings.delivery_cities.store'), [
        'code' => 'moscow_city_map_test',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'center_lat' => 55.7558000,
        'center_lng' => 37.6173000,
        'polygon_json' => json_encode([
            [55.7800, 37.5600],
            [55.7800, 37.6800],
            [55.7000, 37.6800],
            [55.7000, 37.5600],
        ]),
        'is_active' => 1,
    ])->assertRedirect(route('admin.settings.delivery_cities.index'));

    $city = DeliveryCity::query()->where('code', 'moscow_city_map_test')->latest('id')->firstOrFail();

    postJson(route('admin.settings.delivery_zones.store'), [
        'city_id' => $city->id,
        'code' => 'center',
        'name' => 'Center Zone',
        'polygon_color' => '#ff6600',
        'polygon_fill_opacity' => 0.35,
        'polygon_stroke_opacity' => 0.8,
        'polygon_json' => json_encode([
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ]),
        'delivery_time_minutes' => 60,
        'is_active' => 1,
        'inventory_source_ids' => $inventorySource->id,
        'rates' => [
            ['min_order_total' => 0, 'price' => 300, 'sort_order' => 0],
        ],
    ])->assertRedirect(route('admin.settings.delivery_zones.index'));

    $zone = DeliveryZone::query()->where('code', 'center')->latest('id')->firstOrFail();

    expect($zone->name)->toBe('Center Zone')
        ->and($zone->polygon_color)->toBe('#ff6600')
        ->and($zone->rates()->count())->toBe(1)
        ->and($zone->inventory_sources()->count())->toBe(1);
});

it('should render selected inactive city on delivery zone edit page', function () {
    $this->loginAsAdmin();

    $city = DeliveryCity::query()->create([
        'code' => 'nn',
        'name' => 'Nizhny Novgorod',
        'country' => 'RU',
        'state' => 'NIZ',
        'is_active' => false,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'nn-center',
        'name' => 'NN Center',
        'polygon_json' => [],
        'polygon_color' => '#0077cc',
        'polygon_fill_opacity' => 0.2,
        'polygon_stroke_opacity' => 1,
        'is_active' => true,
    ]);

    get(route('admin.settings.delivery_zones.edit', $zone->id))
        ->assertOk()
        ->assertSeeText('Nizhny Novgorod')
        ->assertSee('name="city_id"', false)
        ->assertSee('value="'.$city->id.'"', false);
});

it('should render city zones management page with city zones map', function () {
    $this->loginAsAdmin();

    $city = DeliveryCity::query()->create([
        'code' => 'city-zones-page',
        'name' => 'City Zones Page',
        'country' => 'RU',
        'state' => 'MSK',
        'center_lat' => 55.7512440,
        'center_lng' => 37.6184230,
        'polygon_json' => [
            [55.7800, 37.5600],
            [55.7800, 37.6800],
            [55.7000, 37.6800],
            [55.7000, 37.5600],
        ],
        'is_active' => true,
    ]);

    DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'city-zones-page-main',
        'name' => 'Main Zone',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.6500],
            [55.7200, 37.6500],
            [55.7200, 37.6000],
        ],
        'polygon_color' => '#0077cc',
        'polygon_fill_opacity' => 0.2,
        'polygon_stroke_opacity' => 1,
        'is_active' => true,
    ]);

    $response = get(route('admin.settings.delivery_cities.zones', $city->id));
    $response->assertOk();
    $response->assertSee('Main Zone');
    expect(str_contains($response->getContent(), 'Manage Delivery Zones')
        || str_contains($response->getContent(), 'Управление зонами доставки'))->toBeTrue();
    $response->assertSee('id="city-zones-map"', false);
    $response->assertSee('id="add-zone-button"', false);
    $response->assertSee("map.behaviors.disable('dblClickZoom');", false);
    $response->assertSee("map.events.add('dblclick', (event) => {", false);
    $response->assertSee('data-zone-item=', false);
});

it('should show manage zones link on delivery city edit page', function () {
    $this->loginAsAdmin();

    $city = DeliveryCity::query()->create([
        'code' => 'city-edit-manage-zones-link',
        'name' => 'Manage Zones Link',
        'country' => 'RU',
        'state' => 'MSK',
        'is_active' => true,
    ]);

    get(route('admin.settings.delivery_cities.edit', $city->id))
        ->assertOk()
        ->assertSee(route('admin.settings.delivery_cities.zones', $city->id), false)
        ->assertSeeText('Manage Zones');
});

it('should include manage zones route in delivery cities datagrid actions', function () {
    $this->loginAsAdmin();

    $city = DeliveryCity::query()->create([
        'code' => 'city-datagrid-zones-action',
        'name' => 'Datagrid Action City',
        'country' => 'RU',
        'state' => 'MSK',
        'is_active' => true,
    ]);

    $response = get(route('admin.settings.delivery_cities.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertSeeText('Manage Zones');

    expect($response->getContent())->toContain(str_replace('/', '\\/', route('admin.settings.delivery_cities.zones', $city->id)));
});

it('should redirect back to city zones page after create and update when redirect city is provided', function () {
    $this->loginAsAdmin();

    $inventorySource = InventorySource::factory()->create();

    $city = DeliveryCity::query()->create([
        'code' => 'city-zones-redirect',
        'name' => 'City Redirect',
        'country' => 'RU',
        'state' => 'MSK',
        'is_active' => true,
    ]);

    postJson(route('admin.settings.delivery_zones.store'), [
        'city_id' => $city->id,
        'redirect_city_id' => $city->id,
        'code' => 'city-zones-redirect-main',
        'name' => 'Redirect Main Zone',
        'polygon_color' => '#0077cc',
        'polygon_fill_opacity' => 0.25,
        'polygon_stroke_opacity' => 1,
        'polygon_json' => json_encode([
            [55.7600, 37.6000],
            [55.7600, 37.6500],
            [55.7200, 37.6500],
            [55.7200, 37.6000],
        ]),
        'delivery_time_minutes' => 45,
        'is_active' => 1,
        'inventory_source_ids' => $inventorySource->id,
        'rates' => [
            ['min_order_total' => 0, 'price' => 250, 'sort_order' => 0],
        ],
    ])->assertRedirect(route('admin.settings.delivery_cities.zones', $city->id));

    $zone = DeliveryZone::query()->where('code', 'city-zones-redirect-main')->latest('id')->firstOrFail();

    putJson(route('admin.settings.delivery_zones.update', $zone->id), [
        'city_id' => $city->id,
        'redirect_city_id' => $city->id,
        'code' => 'city-zones-redirect-main',
        'name' => 'Redirect Main Zone Updated',
        'polygon_color' => '#ff6600',
        'polygon_fill_opacity' => 0.3,
        'polygon_stroke_opacity' => 1,
        'polygon_json' => json_encode([
            [55.7600, 37.6000],
            [55.7600, 37.6600],
            [55.7200, 37.6600],
            [55.7200, 37.6000],
        ]),
        'delivery_time_minutes' => 50,
        'is_active' => 1,
        'inventory_source_ids' => $inventorySource->id,
        'rates' => [
            ['min_order_total' => 0, 'price' => 300, 'sort_order' => 0],
        ],
    ])->assertRedirect(route('admin.settings.delivery_cities.zones', $city->id));
});

it('should bind selected city and inventory source values on delivery zone edit page', function () {
    $this->loginAsAdmin();

    $city = DeliveryCity::query()->create([
        'code' => 'kazan',
        'name' => 'Kazan',
        'country' => 'RU',
        'state' => 'TA',
        'is_active' => true,
    ]);

    $inventorySource = InventorySource::factory()->create();

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'kazan-center',
        'name' => 'Kazan Center',
        'polygon_json' => [],
        'polygon_color' => '#0077cc',
        'polygon_fill_opacity' => 0.2,
        'polygon_stroke_opacity' => 1,
        'is_active' => true,
    ]);

    $zone->inventory_sources()->sync([$inventorySource->id]);

    $response = get(route('admin.settings.delivery_zones.edit', $zone->id))
        ->assertOk();

    $content = $response->getContent();

    expect((bool) preg_match('/<v-field(?=[^>]*name="city_id")(?=[^>]*(?:value|:value)="[^"]*'.$city->id.'[^"]*")[^>]*>/', $content))->toBeTrue();
    expect((bool) preg_match('/<v-field(?=[^>]*name="inventory_source_ids")(?=[^>]*(?:value|:value)="[^"]*'.$inventorySource->id.'[^"]*")[^>]*>/', $content))->toBeTrue();
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
        ->assertSee('const parseCoordinatesFromJson = (jsonValue) => {', false)
        ->assertSee('id="polygon-edit-mode"', false)
        ->assertSee('id="apply-polygon-json"', false)
        ->assertSee('id="polygon_color"', false)
        ->assertSee('name="polygon_fill_opacity"', false)
        ->assertSee('name="polygon_stroke_opacity"', false)
        ->assertSee('name="inventory_source_ids"', false)
        ->assertDontSee('name="inventory_source_ids[]"', false)
        ->assertDontSee('name="center_lat"', false)
        ->assertDontSee('name="center_lng"', false)
        ->assertSee('window.setTimeout(initDeliveryZoneForm, 0);', false);

    get(route('admin.settings.delivery_zones.edit', $zone->id))
        ->assertOk()
        ->assertSee('api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=test-yandex-key', false)
        ->assertSee('const initDeliveryZoneForm = () => {', false)
        ->assertSee('const syncPolygonInput = (value) => {', false)
        ->assertSee('const parseCoordinatesFromJson = (jsonValue) => {', false)
        ->assertSee('id="polygon-edit-mode"', false)
        ->assertSee('id="apply-polygon-json"', false)
        ->assertSee('id="polygon_color"', false)
        ->assertSee('name="polygon_fill_opacity"', false)
        ->assertSee('name="polygon_stroke_opacity"', false)
        ->assertSee('name="inventory_source_ids"', false)
        ->assertDontSee('name="inventory_source_ids[]"', false)
        ->assertDontSee('name="center_lat"', false)
        ->assertDontSee('name="center_lng"', false)
        ->assertSee('window.setTimeout(initDeliveryZoneForm, 0);', false);
});

it('should append yandex maps api key to delivery city map script url on create and edit pages', function () {
    $this->loginAsAdmin();

    config()->set('services.yandex_maps.api_key', 'test-yandex-key');

    $city = DeliveryCity::query()->create([
        'code' => 'yekb',
        'name' => 'Yekaterinburg',
        'country' => 'RU',
        'state' => 'SVE',
        'center_lat' => 56.8380110,
        'center_lng' => 60.5974650,
        'polygon_json' => [],
        'is_active' => true,
    ]);

    get(route('admin.settings.delivery_cities.create'))
        ->assertOk()
        ->assertSee('api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=test-yandex-key', false)
        ->assertSee('const initDeliveryCityForm = () => {', false)
        ->assertSee('id="city-map"', false)
        ->assertSee('id="set-center-mode"', false)
        ->assertSee('id="polygon-edit-mode"', false)
        ->assertSee('name="center_lat"', false)
        ->assertSee('name="center_lng"', false)
        ->assertSee('name="polygon_json"', false);

    get(route('admin.settings.delivery_cities.edit', $city->id))
        ->assertOk()
        ->assertSee('api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=test-yandex-key', false)
        ->assertSee('const initDeliveryCityForm = () => {', false)
        ->assertSee('id="city-map"', false)
        ->assertSee('id="set-center-mode"', false)
        ->assertSee('id="polygon-edit-mode"', false)
        ->assertSee('name="center_lat"', false)
        ->assertSee('name="center_lng"', false)
        ->assertSee('name="polygon_json"', false);
});

it('resolves delivery zones admin translation keys in english and russian', function () {
    app()->setLocale('en');

    expect(__('admin::app.settings.delivery_zones.cities-index.heading'))->toBe('Delivery Cities')
        ->and(__('admin::app.settings.delivery_zones.response.city-created'))->toBe('Delivery city created successfully.')
        ->and(__('admin::app.settings.delivery_zones.datagrid.cities.manage-zones'))->toBe('Manage Zones');

    app()->setLocale('ru');

    expect(__('admin::app.settings.delivery_zones.cities-index.heading'))->toBe('Города доставки')
        ->and(__('admin::app.settings.delivery_zones.response.city-created'))->toBe('Город доставки успешно создан.')
        ->and(__('admin::app.components.layouts.sidebar.delivery-cities'))->toBe('Города доставки');
});

it('should allow creating a delivery zone without a city', function () {
    $zone = \Webkul\Shipping\Models\DeliveryZone::query()->create([
        'city_id'                => null,
        'code'                   => 'cityless-zone-test',
        'name'                   => 'Cityless Zone',
        'polygon_json'           => [],
        'polygon_color'          => '#0077cc',
        'polygon_fill_opacity'   => 0.2,
        'polygon_stroke_opacity' => 1.0,
        'is_active'              => true,
    ]);

    expect($zone->city_id)->toBeNull()
        ->and($zone->code)->toBe('cityless-zone-test');
});

it('should render the import delivery zones page', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.delivery_zones.import'))
        ->assertOk()
        ->assertSee(route('admin.settings.delivery_zones.import.store'), false)
        ->assertSee('name="file"', false);
});

it('should import delivery zones from a GeoJSON file', function () {
    $this->loginAsAdmin();

    $city = \Webkul\Shipping\Models\DeliveryCity::query()->create([
        'code'      => 'novosibirsksuharnayaz4',
        'name'      => 'Novosibirsk Test',
        'country'   => 'RU',
        'state'     => 'NSK',
        'is_active' => true,
    ]);

    $inventorySource = InventorySource::factory()->create();

    $geojson = [
        'type'     => 'FeatureCollection',
        'metadata' => ['name' => 'Delivery Zones', 'creator' => 'Admin App Zone Editor'],
        'features' => [
            [
                'type' => 'Feature',
                'id'   => 0,
                'geometry' => [
                    'type'        => 'Polygon',
                    'coordinates' => [
                        [[82.93, 55.24], [82.94, 55.22], [82.96, 55.23], [82.93, 55.24]],
                    ],
                ],
                'properties' => [
                    'description'    => '#cid=novosibirsksuharnayaz4',
                    'fill'           => '#b51eff',
                    'fill-opacity'   => 0.1,
                    'stroke'         => '#b51eff',
                    'stroke-width'   => '1',
                    'stroke-opacity' => 0.1,
                ],
            ],
        ],
    ];

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        'zones.json',
        json_encode($geojson)
    );

    post(route('admin.settings.delivery_zones.import.store'), [
        'file'                => $file,
        'inventory_source_id' => $inventorySource->id,
        'default_rate'        => ['min_order_total' => 0, 'price' => 300],
    ])->assertRedirect(route('admin.settings.delivery_zones.index'));

    $zone = \Webkul\Shipping\Models\DeliveryZone::query()
        ->where('code', 'novosibirsksuharnayaz4')
        ->firstOrFail();

    expect($zone->city_id)->toBe($city->id)
        ->and($zone->polygon_color)->toBe('#b51eff')
        ->and($zone->polygon_fill_opacity)->toBe(0.1)
        ->and($zone->polygon_stroke_opacity)->toBe(0.1)
        ->and($zone->rates()->count())->toBe(1)
        ->and((float) $zone->rates()->first()->price)->toBe(300.0)
        ->and($zone->inventory_sources()->count())->toBe(1);
});

it('should fall back to default city when zone city code is not found', function () {
    $this->loginAsAdmin();

    $defaultCity = \Webkul\Shipping\Models\DeliveryCity::query()->create([
        'code'      => 'default-fallback-city',
        'name'      => 'Fallback City',
        'country'   => 'RU',
        'state'     => 'NSK',
        'is_active' => true,
    ]);

    $inventorySource = InventorySource::factory()->create();

    $geojson = [
        'type'     => 'FeatureCollection',
        'features' => [
            [
                'type' => 'Feature',
                'id'   => 0,
                'geometry' => [
                    'type'        => 'Polygon',
                    'coordinates' => [
                        [[82.93, 55.24], [82.94, 55.22], [82.96, 55.23], [82.93, 55.24]],
                    ],
                ],
                'properties' => [
                    'description'    => '#cid=unknown-city-xyz',
                    'fill'           => '#ff0000',
                    'fill-opacity'   => 0.2,
                    'stroke'         => '#ff0000',
                    'stroke-width'   => '1',
                    'stroke-opacity' => 0.8,
                ],
            ],
        ],
    ];

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        'zones.json',
        json_encode($geojson)
    );

    post(route('admin.settings.delivery_zones.import.store'), [
        'file'                => $file,
        'inventory_source_id' => $inventorySource->id,
        'default_city_id'     => $defaultCity->id,
        'default_rate'        => ['min_order_total' => 0, 'price' => 150],
    ])->assertRedirect(route('admin.settings.delivery_zones.index'));

    $zone = \Webkul\Shipping\Models\DeliveryZone::query()
        ->where('code', 'unknown-city-xyz')
        ->firstOrFail();

    expect($zone->city_id)->toBe($defaultCity->id);
});

it('should export delivery zones as GeoJSON download', function () {
    $this->loginAsAdmin();

    $city = \Webkul\Shipping\Models\DeliveryCity::query()->create([
        'code'      => 'export-test-city',
        'name'      => 'Export Test City',
        'country'   => 'RU',
        'state'     => 'MSK',
        'is_active' => true,
    ]);

    \Webkul\Shipping\Models\DeliveryZone::query()->create([
        'city_id'                => $city->id,
        'code'                   => 'export-test-city',
        'name'                   => 'export-test-city',
        'polygon_json'           => [[82.93, 55.24], [82.94, 55.22], [82.96, 55.23]],
        'polygon_color'          => '#b51eff',
        'polygon_fill_opacity'   => 0.1,
        'polygon_stroke_opacity' => 0.1,
        'is_active'              => true,
    ]);

    $response = get(route('admin.settings.delivery_zones.export'));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');

    $data = json_decode($response->getContent(), true);

    expect($data['type'])->toBe('FeatureCollection')
        ->and($data['features'])->not->toBeEmpty();

    $feature = collect($data['features'])
        ->firstWhere('properties.description', '#cid=export-test-city');

    expect($feature)->not->toBeNull()
        ->and($feature['properties']['fill'])->toBe('#b51eff')
        ->and($feature['properties']['fill-opacity'])->toBe(0.1)
        ->and($feature['properties']['stroke-opacity'])->toBe(0.1)
        ->and($feature['geometry']['type'])->toBe('Polygon');
});
