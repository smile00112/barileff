<?php

use Webkul\Inventory\Models\InventorySource;
use Webkul\User\Models\Admin;

use function Pest\Laravel\getJson;

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

it('isInventorySourceRestricted returns false when no sources are assigned', function () {
    // Arrange.
    $admin = Admin::factory()->create();

    // Act and Assert.
    expect($admin->isInventorySourceRestricted())->toBeFalse();
});

it('isInventorySourceRestricted returns true when sources are assigned', function () {
    // Arrange.
    $admin = Admin::factory()->create();
    $source = InventorySource::factory()->create();

    $admin->inventorySources()->attach($source->id);

    // Act and Assert.
    expect($admin->isInventorySourceRestricted())->toBeTrue();
});

it('getRestrictedInventorySourceIds returns assigned source ids', function () {
    // Arrange.
    $admin = Admin::factory()->create();
    $source = InventorySource::factory()->create();

    $admin->inventorySources()->attach($source->id);

    // Act and Assert.
    expect($admin->getRestrictedInventorySourceIds())->toContain($source->id);
});

it('restricted manager sees only assigned inventory sources in datagrid', function () {
    // Arrange.
    $assignedSource = InventorySource::factory()->create();
    $otherSource = InventorySource::factory()->create();

    $admin = Admin::factory()->create();
    $admin->inventorySources()->attach($assignedSource->id);

    // Act and Assert.
    $this->loginAsAdmin($admin);

    $response = getJson(route('admin.settings.inventory_sources.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertOk();

    $records = collect($response->json('records'));

    expect($records->pluck('id'))->toContain($assignedSource->id)
        ->not->toContain($otherSource->id);
});

it('unrestricted admin sees all inventory sources in datagrid', function () {
    // Arrange.
    $source1 = InventorySource::factory()->create();
    $source2 = InventorySource::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    $response = getJson(route('admin.settings.inventory_sources.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertOk();

    $ids = collect($response->json('records'))->pluck('id');

    expect($ids)->toContain($source1->id)
        ->toContain($source2->id);
});
