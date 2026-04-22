<?php

use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Core\Models\CoreConfig;
use Webkul\Customer\Models\Customer;
use Webkul\DeliveryZones\Services\CartDeliveryZoneManager;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Shipping\Models\DeliveryCity;
use Webkul\Shipping\Models\DeliveryZone;
use Webkul\Shipping\Services\ZoneSelector;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function enableDeliveryZonesCarrier(): void
{
    CoreConfig::factory()->create([
        'code' => 'sales.carriers.delivery_zones.active',
        'value' => 1,
    ])->create([
        'code' => 'sales.carriers.delivery_zones.title',
        'value' => 'Delivery By Zone',
    ])->create([
        'code' => 'sales.carriers.delivery_zones.description',
        'value' => 'Delivery by city zones',
    ]);
}

function createCartWithOneItem(array $cartAttributes = []): Cart
{
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    $cart = Cart::factory()->create($cartAttributes);

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'sku' => $product->sku,
        'quantity' => 1,
        'name' => $product->name,
        'price' => $price = core()->convertPrice(100),
        'base_price' => 100,
        'total' => $price,
        'base_total' => 100,
        'weight' => $product->weight ?? 1,
        'total_weight' => $product->weight ?? 1,
        'base_total_weight' => $product->weight ?? 1,
        'type' => $product->type,
        'additional' => [
            'product_id' => $product->id,
            'quantity' => 1,
        ],
    ]);

    return $cart;
}

it('should resolve delivery zone automatically by coordinates', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create();

    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'center',
        'name' => 'Center',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ],
        'delivery_time_minutes' => 60,
        'is_active' => true,
    ]);

    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 0, 'price' => 350, 'sort_order' => 0]);

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'sub_total' => 500,
        'base_sub_total' => 500,
        'delivery_zone_mode' => 'auto',
        'delivery_point_lat' => 55.7300,
        'delivery_point_lng' => 37.6500,
    ]);

    CartAddress::query()->create([
        'address_type' => CartAddress::ADDRESS_TYPE_SHIPPING,
        'cart_id' => $cart->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'address' => 'Lenina 1',
        'city' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'postcode' => '101000',
        'phone' => '9999999999',
        'default_address' => 0,
    ]);

    $resolvedZone = app(ZoneSelector::class)->resolveZone($cart->fresh());

    expect($resolvedZone?->id)->toBe($zone->id);
});

it('should calculate delivery by selected zone manually', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create();

    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow-manual',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'manual-zone',
        'name' => 'Manual Zone',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ],
        'delivery_time_minutes' => 80,
        'is_active' => true,
    ]);

    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 0, 'price' => 490, 'sort_order' => 0]);

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'sub_total' => 1000,
        'base_sub_total' => 1000,
    ]);

    cart()->setCart($cart);

    postJson(route('shop.api.checkout.cart.estimate_shipping'), [
        'country' => 'RU',
        'state' => 'MOW',
        'city' => 'Moscow',
        'postcode' => '101000',
        'delivery_zone_id' => $zone->id,
    ])->assertOk();

    $cart->refresh();

    expect($cart->delivery_zone_id)->toBe($zone->id)
        ->and($cart->delivery_zone_mode)->toBe('manual');

    getJson(route('shop.api.checkout.cart.index'))
        ->assertOk()
        ->assertJsonPath('data.delivery_zone.id', $zone->id)
        ->assertJsonPath('data.delivery_zone.name', 'Manual Zone');
});

it('should resolve delivery zone mode consistently through service', function () {
    $manager = app(CartDeliveryZoneManager::class);

    expect($manager->resolveMode(55.73, 37.65, 10))->toBe('manual')
        ->and($manager->resolveMode(55.73, 37.65, null))->toBe('auto')
        ->and($manager->resolveMode(null, null, null))->toBeNull();
});

it('preserves delivery zone and inventory source after checkout address submission', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create(['status' => true]);
    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow-preserve',
        'name' => 'Москва',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'preserve-zone',
        'name' => 'Preserve Zone',
        'polygon_json' => [
            [55.70, 37.50],
            [55.80, 37.50],
            [55.80, 37.70],
            [55.70, 37.70],
        ],
        'delivery_time_minutes' => 60,
        'is_active' => true,
    ]);

    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 0, 'price' => 300, 'sort_order' => 0]);

    $customer = Customer::factory()->create();

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'customer_id' => $customer->id,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'customer_email' => $customer->email,
        'is_guest' => 0,
        'sub_total' => 500,
        'base_sub_total' => 500,
        'delivery_zone_id' => $zone->id,
        'delivery_point_lat' => 55.75,
        'delivery_point_lng' => 37.61,
        'delivery_zone_mode' => 'auto',
        'inventory_source_id' => $inventorySource->id,
    ]);

    cart()->setCart($cart);

    $this->loginAsCustomer($customer);

    // Post address WITHOUT delivery_zone_id / lat / lng — simulates the real frontend
    postJson(route('shop.checkout.onepage.addresses.store'), [
        'billing' => [
            'first_name' => 'Ivan',
            'last_name' => 'Petrov',
            'email' => 'ivan@example.com',
            'address' => ['ул. Ленина, 5'],
            'city' => 'Москва',
            'country' => 'RU',
            'state' => 'MOW',
            'postcode' => '101000',
            'phone' => '+79991234567',
            'use_for_shipping' => true,
        ],
    ])->assertStatus(200);

    $cart->refresh();

    expect($cart->delivery_zone_id)->toBe($zone->id)
        ->and($cart->inventory_source_id)->toBe($inventorySource->id)
        ->and($cart->delivery_point_lat)->not->toBeNull();
});

it('should set inventory_source_id when zone is resolved via estimate shipping', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create();
    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow-inv',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'zone-inv',
        'name' => 'Zone With Inventory',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ],
        'delivery_time_minutes' => 60,
        'is_active' => true,
    ]);
    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 0, 'price' => 100, 'sort_order' => 0]);

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'sub_total' => 500,
        'base_sub_total' => 500,
    ]);

    cart()->setCart($cart);

    postJson(route('shop.api.checkout.cart.estimate_shipping'), [
        'country' => 'RU',
        'state' => 'MOW',
        'city' => 'Moscow',
        'postcode' => '101000',
        'delivery_zone_id' => $zone->id,
    ])->assertOk();

    $cart->refresh();

    expect($cart->inventory_source_id)->toBe($inventorySource->id)
        ->and((int) session('selected_inventory_source_id'))->toBe($inventorySource->id);
});

it('carrier selects the correct rate by cart subtotal', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create();
    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow-rate-pick',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'zone-rate-pick',
        'name' => 'Rate Pick Zone',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ],
        'delivery_time_minutes' => 60,
        'is_active' => true,
    ]);
    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 0, 'price' => 300, 'sort_order' => 0]);
    $zone->rates()->create(['min_order_total' => 2000, 'price' => 150, 'sort_order' => 0]);

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'sub_total' => 2500,
        'base_sub_total' => 2500,
    ]);

    cart()->setCart($cart);

    $response = postJson(route('shop.api.checkout.cart.estimate_shipping'), [
        'country' => 'RU',
        'state' => 'MOW',
        'city' => 'Moscow',
        'postcode' => '101000',
        'delivery_zone_id' => $zone->id,
    ])->assertOk()->json();

    $rates = $response['data']['shipping_methods'][0]['rates'] ?? [];

    expect($rates)->not->toBeEmpty()
        ->and((float) $rates[0]['base_price'])->toBe(150.0)
        ->and($rates[0]['method'])->toBe('delivery_zones_delivery_zones');
});

it('carrier keeps delivery rate available when cart subtotal below zone minimum', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create();
    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow-below-min',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'zone-below-min',
        'name' => 'Below Min Zone',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ],
        'delivery_time_minutes' => 60,
        'is_active' => true,
    ]);
    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 500, 'price' => 300, 'sort_order' => 0]);

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'sub_total' => 400,
        'base_sub_total' => 400,
    ]);

    cart()->setCart($cart);

    $response = postJson(route('shop.api.checkout.cart.estimate_shipping'), [
        'country' => 'RU',
        'state' => 'MOW',
        'city' => 'Moscow',
        'postcode' => '101000',
        'delivery_zone_id' => $zone->id,
    ])->assertOk()->json();

    $rates = $response['data']['shipping_methods'][0]['rates'] ?? [];

    expect($rates)->not->toBeEmpty()
        ->and($rates[0]['method'])->toBe('delivery_zones_delivery_zones')
        ->and((float) $rates[0]['base_price'])->toBe(300.0);
});

it('CartResource exposes zone minimum fields and below-minimum flag', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create();
    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow-resource',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'zone-resource',
        'name' => 'Resource Zone',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ],
        'delivery_time_minutes' => 60,
        'is_active' => true,
    ]);
    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 1000, 'price' => 300, 'sort_order' => 0]);
    $zone->rates()->create(['min_order_total' => 2000, 'price' => 0, 'sort_order' => 0]);

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'sub_total' => 800,
        'base_sub_total' => 800,
        'delivery_zone_id' => $zone->id,
        'delivery_zone_mode' => 'manual',
    ]);

    cart()->setCart($cart);

    getJson(route('shop.checkout.onepage.summary'))
        ->assertOk()
        ->assertJsonPath('data.delivery_zone.id', $zone->id)
        ->assertJsonPath('data.delivery_zone.min_order_total', 1000.0)
        ->assertJsonPath('data.delivery_zone.is_below_minimum', true)
        ->assertJsonPath('data.delivery_zone.amount_missing_to_minimum', 200.0);
});

it('validateOrder blocks storeOrder when cart subtotal is below zone minimum', function () {
    enableDeliveryZonesCarrier();

    $inventorySource = InventorySource::factory()->create();
    core()->getCurrentChannel()->inventory_sources()->sync([$inventorySource->id]);

    $city = DeliveryCity::query()->create([
        'code' => 'moscow-validate',
        'name' => 'Moscow',
        'country' => 'RU',
        'state' => 'MOW',
        'is_active' => true,
    ]);

    $zone = DeliveryZone::query()->create([
        'city_id' => $city->id,
        'code' => 'zone-validate',
        'name' => 'Validate Zone',
        'polygon_json' => [
            [55.7600, 37.6000],
            [55.7600, 37.7000],
            [55.7000, 37.7000],
            [55.7000, 37.6000],
        ],
        'delivery_time_minutes' => 60,
        'is_active' => true,
    ]);
    $zone->inventory_sources()->sync([$inventorySource->id]);
    $zone->rates()->create(['min_order_total' => 1000, 'price' => 300, 'sort_order' => 0]);

    $cart = createCartWithOneItem([
        'channel_id' => core()->getCurrentChannel()->id,
        'sub_total' => 500,
        'base_sub_total' => 500,
        'delivery_zone_id' => $zone->id,
        'delivery_zone_mode' => 'manual',
    ]);

    cart()->setCart($cart);

    $response = postJson(route('shop.checkout.onepage.orders.store'));

    $response->assertStatus(500);

    expect($response->json('message'))->toContain('1000')
        ->and($response->json('message'))->toContain('Validate Zone');
});
