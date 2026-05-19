<?php

use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderStatus;
use Webkul\Sales\Models\OrderStatusHistory;
use Webkul\Sales\Models\OrderStatusTransition;
use Webkul\Sales\Services\OrderStatusTransitionService;
use Webkul\Sales\Services\TransitionContext;

use function Pest\Laravel\postJson;

beforeEach(function () {
    // Ensure statuses exist (they come from seeder in production; create manually for tests)
    OrderStatus::firstOrCreate(
        ['code' => 'pending'],
        ['name' => 'Pending', 'sort_order' => 1, 'is_system' => true, 'is_active' => true]
    );

    OrderStatus::firstOrCreate(
        ['code' => 'processing'],
        ['name' => 'Processing', 'sort_order' => 2, 'is_system' => true, 'is_active' => true]
    );

    OrderStatus::firstOrCreate(
        ['code' => 'completed'],
        ['name' => 'Completed', 'sort_order' => 3, 'is_system' => true, 'is_active' => true, 'is_terminal' => true]
    );

    OrderStatus::firstOrCreate(
        ['code' => 'canceled'],
        ['name' => 'Canceled', 'sort_order' => 4, 'is_system' => true, 'is_active' => true, 'is_terminal' => true, 'is_cancel_state' => true]
    );

    // Create a wildcard transition: any → processing
    OrderStatusTransition::firstOrCreate(
        ['from_status_code' => '*', 'to_status_code' => 'processing'],
        ['is_active' => true, 'priority' => 10]
    );

    // Create a wildcard transition: any → completed
    OrderStatusTransition::firstOrCreate(
        ['from_status_code' => '*', 'to_status_code' => 'completed'],
        ['is_active' => true, 'priority' => 10]
    );

    // Create a wildcard transition: any → canceled
    OrderStatusTransition::firstOrCreate(
        ['from_status_code' => '*', 'to_status_code' => 'canceled'],
        ['is_active' => true, 'priority' => 10]
    );
});

function createTestOrder(string $status = 'pending'): Order
{
    return Order::factory()->create([
        'status' => $status,
        'channel_name' => 'Default',
        'customer_email' => 'test@example.com',
    ]);
}

it('transitions order to a valid status and records history', function () {
    $order = createTestOrder('pending');

    /** @var OrderStatusTransitionService $service */
    $service = app(OrderStatusTransitionService::class);

    $result = $service->transition($order, 'processing', TransitionContext::forSystem('test'));

    expect($result->success)->toBeTrue();
    expect($order->fresh()->status)->toBe('processing');

    $history = OrderStatusHistory::where('order_id', $order->id)
        ->where('new_status', 'processing')
        ->first();

    expect($history)->not->toBeNull();
    expect($history->old_status)->toBe('pending');
});

it('returns failure when no transition rule exists for status', function () {
    // Create a status with no rules pointing to it
    OrderStatus::firstOrCreate(
        ['code' => 'custom_blocked'],
        ['name' => 'Custom Blocked', 'sort_order' => 99, 'is_active' => true]
    );

    $order = createTestOrder('pending');

    /** @var OrderStatusTransitionService $service */
    $service = app(OrderStatusTransitionService::class);

    $result = $service->transition($order, 'custom_blocked', TransitionContext::forSystem('test'));

    expect($result->success)->toBeFalse();
    expect($order->fresh()->status)->toBe('pending');
});

it('does not transition to the same status (idempotency)', function () {
    $order = createTestOrder('processing');

    /** @var OrderStatusTransitionService $service */
    $service = app(OrderStatusTransitionService::class);

    $result = $service->transition($order, 'processing', TransitionContext::forSystem('test'));

    // Same status → success but no duplicate history
    expect($result->success)->toBeTrue();

    $historyCount = OrderStatusHistory::where('order_id', $order->id)
        ->where('new_status', 'processing')
        ->count();

    expect($historyCount)->toBeLessThanOrEqual(1);
});

it('blocks transition from terminal status', function () {
    $order = createTestOrder('completed');

    /** @var OrderStatusTransitionService $service */
    $service = app(OrderStatusTransitionService::class);

    $result = $service->transition($order, 'processing', TransitionContext::forSystem('test'));

    expect($result->success)->toBeFalse();
    expect($order->fresh()->status)->toBe('completed');
});

it('admin can update order status via API endpoint', function () {
    $this->loginAsAdmin();

    $order = createTestOrder('pending');

    postJson(route('admin.sales.orders.update_status', $order->id), [
        'status' => 'processing',
        'comment' => 'Moving to processing',
    ])->assertOk()
        ->assertJsonPath('success', true);

    expect($order->fresh()->status)->toBe('processing');
});

it('API returns 422 when transition is not allowed', function () {
    $this->loginAsAdmin();

    OrderStatus::firstOrCreate(
        ['code' => 'no_rule_status'],
        ['name' => 'No Rule Status', 'sort_order' => 98, 'is_active' => true]
    );

    $order = createTestOrder('completed');

    postJson(route('admin.sales.orders.update_status', $order->id), [
        'status' => 'no_rule_status',
    ])->assertStatus(422);
});
