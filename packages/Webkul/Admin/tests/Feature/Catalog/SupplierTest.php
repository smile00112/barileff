<?php

use Webkul\Supplier\Models\Supplier;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should return the supplier index page', function () {
    $this->loginAsAdmin();

    get(route('admin.suppliers.index'))
        ->assertOk()
        ->assertSeeText(trans('supplier::app.admin.index.title'));
});

it('should create a supplier', function () {
    $this->loginAsAdmin();

    postJson(route('admin.suppliers.store'), [
        'name' => 'Test Supplier',
        'contact_email' => 'supplier@example.com',
        'status' => 1,
    ])
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Test Supplier',
        'contact_email' => 'supplier@example.com',
    ]);
});

it('should update a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Old Name',
        'status' => 1,
    ]);

    $this->loginAsAdmin();

    putJson(route('admin.suppliers.update', $supplier->id), [
        'name' => 'New Name',
        'status' => 1,
    ])
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'New Name',
    ]);
});

it('should delete a supplier', function () {
    $supplier = Supplier::create([
        'name' => 'Delete Me',
        'status' => 1,
    ]);

    $this->loginAsAdmin();

    deleteJson(route('admin.suppliers.destroy', $supplier->id))
        ->assertOk();

    $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
});

it('should return 404 when editing a non-existent supplier', function () {
    $this->loginAsAdmin();

    get(route('admin.suppliers.edit', 999999))
        ->assertNotFound();
});
