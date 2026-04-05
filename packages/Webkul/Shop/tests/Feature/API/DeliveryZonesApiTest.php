<?php

use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Core\Models\CoreConfig;
use Webkul\Faker\Helpers\Product as ProductFaker;
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
        'polygon_json' => [
            [55.76, 37.60],
            [55.76, 37.70],
            [55.70, 37.70],
            [55.70, 37.60],
        ],
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

it('returns delivery zones for map with zone rates', function () {
    $this->zone->rates()->create([
        'min_order_total' => 0,
        'price' => 350,
        'sort_order' => 0,
    ]);
    $this->zone->rates()->create([
        'min_order_total' => 5000,
        'price' => 0,
        'sort_order' => 10,
    ]);

    $response = getJson(route('shop.api.delivery_zones.index'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'center_lat',
                    'center_lng',
                    'polygon_json',
                    'zones' => [
                        '*' => [
                            'id',
                            'name',
                            'polygon_json',
                            'polygon_color',
                            'inventory_source_id',
                            'rates' => [
                                '*' => [
                                    'min_order_total',
                                    'price',
                                    'sort_order',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

    $cityPayload = collect($response->json('data'))->firstWhere('id', $this->city->id);
    expect($cityPayload)->not->toBeNull()
        ->and($cityPayload['name'])->toBe('Test City')
        ->and($cityPayload['polygon_json'][0][0])->toBe(55.76)
        ->and($cityPayload['polygon_json'][0][1])->toBe(37.60)
        ->and($cityPayload['zones'][0]['name'])->toBe('Test Zone')
        ->and($cityPayload['zones'][0]['inventory_source_id'])->toBe($this->inventorySource->id)
        ->and($cityPayload['zones'][0]['rates'][0]['min_order_total'])->toBe(5000.0)
        ->and($cityPayload['zones'][0]['rates'][0]['price'])->toBe(0.0)
        ->and($cityPayload['zones'][0]['rates'][1]['min_order_total'])->toBe(0.0)
        ->and($cityPayload['zones'][0]['rates'][1]['price'])->toBe(350.0);
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

it('applies zone and shipping method to cart when select with shipping_method', function () {
    CoreConfig::factory()->create([
        'code' => 'sales.carriers.delivery_zones.title',
        'value' => 'Delivery By Zone',
    ]);
    CoreConfig::factory()->create([
        'code' => 'sales.carriers.delivery_zones.description',
        'value' => 'Delivery by city zones',
    ]);

    $this->zone->rates()->create(['min_order_total' => 0, 'price' => 350, 'sort_order' => 0]);

    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $cart = Cart::factory()->create([
        'channel_id' => core()->getCurrentChannel()->id,
    ]);
    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'sku' => $product->sku,
        'quantity' => 1,
        'name' => $product->name,
        'price' => core()->convertPrice(100),
        'base_price' => 100,
        'total' => core()->convertPrice(100),
        'base_total' => 100,
        'weight' => $product->weight ?? 1,
        'total_weight' => $product->weight ?? 1,
        'base_total_weight' => $product->weight ?? 1,
        'type' => $product->type,
        'additional' => ['product_id' => $product->id, 'quantity' => 1],
    ]);

    CartAddress::query()->create([
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
        'cart_id' => $cart->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'address' => 'Lenina 1',
        'city' => 'Test City',
        'country' => 'RU',
        'state' => 'MOW',
        'postcode' => '101000',
        'phone' => '9999999999',
        'default_address' => 0,
    ]);

    cart()->setCart($cart);

    $response = postJson(route('shop.api.delivery_zones.select'), [
        'delivery_zone_id' => $this->zone->id,
        'shipping_method' => 'delivery_zones_delivery_zones',
        '_token' => csrf_token(),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.inventory_source_id', $this->inventorySource->id)
        ->assertJsonPath('data.zone.id', $this->zone->id)
        ->assertJsonPath('data.cart.shipping_method', 'delivery_zones_delivery_zones')
        ->assertJsonPath('data.cart.delivery_zone.id', $this->zone->id);

    expect($response->json('data.cart.shipping_amount'))->toBeGreaterThan(0);
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
