<?php

use Webkul\Sales\Models\OrderStatus;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('returns order statuses index page', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.order_statuses.index'))
        ->assertOk();
});

it('can create a non-system order status', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.order_statuses.store'), [
        'code' => 'test_custom_status',
        'name' => 'Test Custom Status',
        'sort_order' => 50,
        'is_active' => true,
        'is_terminal' => false,
        'is_cancel_state' => false,
        'is_payment_required' => false,
    ])->assertOk();

    $this->assertDatabaseHas('order_statuses', ['code' => 'test_custom_status']);
});

it('can update a non-system order status', function () {
    $this->loginAsAdmin();

    $status = OrderStatus::create([
        'code' => 'updatable_status',
        'name' => 'Old Name',
        'sort_order' => 60,
        'is_system' => false,
        'is_active' => true,
    ]);

    putJson(route('admin.settings.order_statuses.update', $status->id), [
        'name' => 'New Name',
        'sort_order' => 61,
        'is_active' => true,
        'is_terminal' => false,
        'is_cancel_state' => false,
        'is_payment_required' => false,
    ])->assertOk();

    expect($status->fresh()->name)->toBe('New Name');
});

it('cannot delete a system order status', function () {
    $this->loginAsAdmin();

    $status = OrderStatus::firstOrCreate(
        ['code' => 'pending'],
        ['name' => 'Pending', 'sort_order' => 1, 'is_system' => true, 'is_active' => true]
    );

    delete(route('admin.settings.order_statuses.destroy', $status->id))
        ->assertStatus(422);

    $this->assertDatabaseHas('order_statuses', ['code' => 'pending']);
});

it('can delete a non-system order status', function () {
    $this->loginAsAdmin();

    $status = OrderStatus::create([
        'code' => 'deletable_status',
        'name' => 'Deletable',
        'sort_order' => 70,
        'is_system' => false,
        'is_active' => true,
    ]);

    delete(route('admin.settings.order_statuses.destroy', $status->id))
        ->assertOk();

    $this->assertDatabaseMissing('order_statuses', ['code' => 'deletable_status']);
});
