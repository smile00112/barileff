<?php

use Webkul\Inventory\Models\InventorySource;
use Webkul\User\Models\Admin;

it('can attach an inventory source to an admin', function () {
    $admin = Admin::factory()->create();
    $inventorySource = InventorySource::factory()->create();

    $admin->inventorySources()->attach($inventorySource->id);

    $this->assertDatabaseHas('admin_inventory_sources', [
        'admin_id' => $admin->id,
        'inventory_source_id' => $inventorySource->id,
    ]);
});

it('can query admins attached to an inventory source', function () {
    $admin = Admin::factory()->create();
    $inventorySource = InventorySource::factory()->create();

    $admin->inventorySources()->attach($inventorySource->id);

    expect($inventorySource->admins()->where('admins.id', $admin->id)->exists())->toBeTrue();
});

it('detaches pivot rows when admin is deleted', function () {
    $admin = Admin::factory()->create();
    $inventorySource = InventorySource::factory()->create();

    $admin->inventorySources()->attach($inventorySource->id);

    $adminId = $admin->id;
    $admin->delete();

    $this->assertDatabaseMissing('admin_inventory_sources', [
        'admin_id' => $adminId,
    ]);
});
