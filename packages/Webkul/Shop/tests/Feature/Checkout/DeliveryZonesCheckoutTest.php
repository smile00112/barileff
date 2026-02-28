<?php

use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Checkout\Models\CartItem;
use Webkul\Core\Models\CoreConfig;
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
