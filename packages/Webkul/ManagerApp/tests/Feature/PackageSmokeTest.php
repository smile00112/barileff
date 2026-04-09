<?php

use Webkul\ManagerApp\Tests\ManagerAppTestCase;

uses(ManagerAppTestCase::class);

it('has inventory_source_id column on orders table', function () {
    expect(\Illuminate\Support\Facades\Schema::hasColumn('orders', 'inventory_source_id'))->toBeTrue();
});

it('creates a manager with an inventory source assigned', function () {
    $admin = $this->createManager();

    expect($admin->inventorySources()->exists())->toBeTrue();
    expect($admin->isInventorySourceRestricted())->toBeTrue();
});

it('returns 401 when unauthenticated request hits manager api', function () {
    $response = $this->getJson('/manager/api/orders');

    $response->assertStatus(401);
});
