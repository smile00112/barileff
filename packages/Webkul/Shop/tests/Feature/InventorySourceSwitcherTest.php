<?php

use Webkul\Checkout\Models\Cart;
use Webkul\Inventory\Models\InventorySource;
use Webkul\User\Models\Admin;

use function Pest\Laravel\post;

beforeEach(function () {
    $this->firstSource = InventorySource::factory()->create();
    $this->secondSource = InventorySource::factory()->create();

    core()->getCurrentChannel()->inventory_sources()->sync([
        $this->firstSource->id,
        $this->secondSource->id,
    ]);
});

it('forbids guests from switching inventory source in storefront', function () {
    post(route('shop.inventory_sources.switch'), [
        'inventory_source_id' => $this->firstSource->id,
    ])->assertForbidden();
});

it('allows authenticated admin to switch inventory source and updates cart session state', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin');

    $cart = Cart::factory()->create([
        'channel_id' => core()->getCurrentChannel()->id,
        'inventory_source_id' => $this->firstSource->id,
    ]);

    cart()->setCart($cart);

    post(route('shop.inventory_sources.switch'), [
        'inventory_source_id' => $this->secondSource->id,
    ])->assertRedirect();

    expect((int) session('selected_inventory_source_id'))->toBe($this->secondSource->id)
        ->and((int) $cart->fresh()->inventory_source_id)->toBe($this->secondSource->id);
});
