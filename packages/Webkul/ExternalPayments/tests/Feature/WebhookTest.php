<?php

use Illuminate\Support\Facades\Config;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Config::set('payment_methods.external_payments.api_token', 'test-secret-token');
});

it('returns 400 when payload is missing order_id', function () {
    postJson(route('external-payments.webhook'), ['payment_status' => 'paid'], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertStatus(400);
});

it('returns 400 when payload is missing payment_status', function () {
    postJson(route('external-payments.webhook'), ['order_id' => 1], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertStatus(400);
});

it('returns 401 when bearer token is wrong', function () {
    $order = Order::factory()->pending()->create();
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'external_payments']);

    postJson(route('external-payments.webhook'), [
        'order_id' => $order->id,
        'payment_status' => 'paid',
    ], [
        'Authorization' => 'Bearer wrong-token',
    ])->assertStatus(401);
});

it('returns 404 when order does not exist', function () {
    postJson(route('external-payments.webhook'), [
        'order_id' => 999999,
        'payment_status' => 'paid',
    ], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertStatus(404);
});

it('returns 404 when order uses a different payment method', function () {
    $order = Order::factory()->pending()->create();
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'cashondelivery']);

    postJson(route('external-payments.webhook'), [
        'order_id' => $order->id,
        'payment_status' => 'paid',
    ], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertStatus(404);
});

it('transitions order to processing on paid status', function () {
    $order = Order::factory()->pending()->create(['grand_total' => 100]);
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'external_payments']);

    Config::set('core.sales.payment_methods.external_payments.paid_order_status', 'processing');

    postJson(route('external-payments.webhook'), [
        'order_id' => $order->id,
        'payment_status' => 'paid',
    ], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($order->fresh()->status)->toBe('processing');
});

it('transitions order to canceled on failed status', function () {
    $order = Order::factory()->pending()->create();
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'external_payments']);

    postJson(route('external-payments.webhook'), [
        'order_id' => $order->id,
        'payment_status' => 'failed',
    ], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertSuccessful()
        ->assertJson(['success' => true]);

    expect($order->fresh()->status)->toBe('canceled');
});

it('transitions order to canceled on declined status', function () {
    $order = Order::factory()->pending()->create();
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'external_payments']);

    postJson(route('external-payments.webhook'), [
        'order_id' => $order->id,
        'payment_status' => 'declined',
    ], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertSuccessful();

    expect($order->fresh()->status)->toBe('canceled');
});

it('accepts all known successful payment statuses', function (string $status) {
    $order = Order::factory()->pending()->create(['grand_total' => 50]);
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'external_payments']);

    Config::set('core.sales.payment_methods.external_payments.paid_order_status', 'processing');

    postJson(route('external-payments.webhook'), [
        'order_id' => $order->id,
        'payment_status' => $status,
    ], [
        'Authorization' => 'Bearer test-secret-token',
    ])->assertSuccessful();

    expect($order->fresh()->status)->toBe('processing');
})->with(['paid', 'completed', 'approved', 'processing']);

it('allows webhook without bearer token when no api_token configured', function () {
    Config::set('payment_methods.external_payments.api_token', null);

    $order = Order::factory()->pending()->create();
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'external_payments']);

    postJson(route('external-payments.webhook'), [
        'order_id' => $order->id,
        'payment_status' => 'cancelled',
    ])->assertSuccessful();

    expect($order->fresh()->status)->toBe('canceled');
});
