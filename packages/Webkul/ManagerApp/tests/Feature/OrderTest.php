<?php

use Webkul\ManagerApp\Services\ManagerOrderService;
use Webkul\ManagerApp\Tests\ManagerAppTestCase;
use Webkul\Sales\Models\Order;

uses(ManagerAppTestCase::class);

it('returns statuses list when authenticated', function () {
    [$admin, $token] = $this->loginAsManager();

    $response = $this->withToken($token)->getJson('/manager/api/orders/statuses');

    $response->assertStatus(200)
        ->assertJsonStructure(['processing', 'completed', 'canceled', 'awaiting_confirmation']);
});

it('returns paginated orders for manager warehouse', function () {
    $admin = $this->createManager();

    $sourceId = $admin->inventorySources()->first()->id;

    // Create orders for this warehouse
    Order::factory()->count(3)->create(['inventory_source_id' => $sourceId]);

    // Create an order for a different warehouse (should not appear)
    Order::factory()->create(['inventory_source_id' => null]);

    $token = $admin->createToken('manager-app')->plainTextToken;

    $response = $this->withToken($token)->getJson('/manager/api/orders');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

    expect($response->json('meta.total'))->toBe(3);
});

it('returns 404 for order belonging to another warehouse', function () {
    [$admin, $token] = $this->loginAsManager();

    $otherOrder = Order::factory()->create(['inventory_source_id' => null]);

    $response = $this->withToken($token)->getJson("/manager/api/orders/{$otherOrder->id}");

    $response->assertStatus(404);
});

it('returns order detail for own warehouse order', function () {
    $admin = $this->createManager();
    $sourceId = $admin->inventorySources()->first()->id;
    $order = Order::factory()->create(['inventory_source_id' => $sourceId]);
    $token = $admin->createToken('manager-app')->plainTextToken;

    $response = $this->withToken($token)->getJson("/manager/api/orders/{$order->id}");

    $response->assertStatus(200)
        ->assertJsonPath('id', $order->id)
        ->assertJsonPath('increment_id', $order->increment_id);
});

it('updates order status successfully', function () {
    $admin = $this->createManager();
    $sourceId = $admin->inventorySources()->first()->id;
    $order = Order::factory()->create([
        'inventory_source_id' => $sourceId,
        'status' => Order::STATUS_PENDING,
    ]);
    $token = $admin->createToken('manager-app')->plainTextToken;

    $response = $this->withToken($token)->patchJson("/manager/api/orders/{$order->id}/status", [
        'status' => Order::STATUS_PROCESSING,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', Order::STATUS_PROCESSING);

    expect(Order::find($order->id)->status)->toBe(Order::STATUS_PROCESSING);
});

it('rejects invalid status value', function () {
    $admin = $this->createManager();
    $sourceId = $admin->inventorySources()->first()->id;
    $order = Order::factory()->create(['inventory_source_id' => $sourceId]);
    $token = $admin->createToken('manager-app')->plainTextToken;

    $response = $this->withToken($token)->patchJson("/manager/api/orders/{$order->id}/status", [
        'status' => 'invalid_status',
    ]);

    $response->assertStatus(422);
});

it('rejects status not in allowed list', function () {
    $admin = $this->createManager();
    $sourceId = $admin->inventorySources()->first()->id;
    $order = Order::factory()->create(['inventory_source_id' => $sourceId]);
    $token = $admin->createToken('manager-app')->plainTextToken;

    $response = $this->withToken($token)->patchJson("/manager/api/orders/{$order->id}/status", [
        'status' => Order::STATUS_FRAUD, // not in allowed list
    ]);

    $response->assertStatus(422);
});
