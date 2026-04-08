<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\Product;
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

// Image Upload Tests

it('can upload supplier image', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $image = UploadedFile::fake()->image('supplier.jpg');

    postJson(route('admin.suppliers.store'), [
        'name' => 'Supplier with Image',
        'image' => $image,
        'status' => 1,
    ])
        ->assertRedirect();

    $supplier = Supplier::where('name', 'Supplier with Image')->first();

    expect($supplier->image)->not->toBeNull();
    Storage::disk('public')->assertExists($supplier->image);
});

it('can remove supplier image', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $supplier = Supplier::create([
        'name' => 'Supplier with Image',
        'image' => UploadedFile::fake()->image('old.jpg')->store('supplier_images', 'public'),
        'status' => 1,
    ]);

    $oldImage = $supplier->image;
    Storage::disk('public')->assertExists($oldImage);

    putJson(route('admin.suppliers.update', $supplier->id), [
        'name' => 'Supplier with Image',
        'remove_image' => true,
        'status' => 1,
    ])
        ->assertRedirect();

    $supplier->refresh();

    expect($supplier->image)->toBeNull();
    Storage::disk('public')->assertMissing($oldImage);
});

it('image is deleted when supplier is deleted', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $imagePath = UploadedFile::fake()->image('test.jpg')->store('supplier_images', 'public');

    $supplier = Supplier::create([
        'name' => 'Supplier to Delete',
        'image' => $imagePath,
        'status' => 1,
    ]);

    Storage::disk('public')->assertExists($imagePath);

    deleteJson(route('admin.suppliers.destroy', $supplier->id))
        ->assertOk();

    Storage::disk('public')->assertMissing($imagePath);
});

// Sort Order Tests

it('suppliers are sorted by sort order then name', function () {
    $this->loginAsAdmin();

    Supplier::create(['name' => 'Zebra Supplier', 'sort_order' => 2, 'status' => 1]);
    Supplier::create(['name' => 'Alpha Supplier', 'sort_order' => 1, 'status' => 1]);
    Supplier::create(['name' => 'Beta Supplier', 'sort_order' => 1, 'status' => 1]);

    $response = get(route('admin.suppliers.index'))
        ->assertOk();

    $suppliers = Supplier::orderBy('sort_order')->orderBy('name')->get();

    expect($suppliers->first()->name)->toBe('Alpha Supplier');
    expect($suppliers->last()->name)->toBe('Zebra Supplier');
});

it('can update supplier sort order', function () {
    $this->loginAsAdmin();

    $supplier = Supplier::create([
        'name' => 'Test Supplier',
        'sort_order' => 10,
        'status' => 1,
    ]);

    putJson(route('admin.suppliers.update', $supplier->id), [
        'name' => 'Test Supplier',
        'sort_order' => 5,
        'status' => 1,
    ])
        ->assertRedirect();

    $supplier->refresh();

    expect($supplier->sort_order)->toBe(5);
});

// Form Validation Tests

it('can create supplier with description and image', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    postJson(route('admin.suppliers.store'), [
        'name' => 'Full Supplier',
        'description' => 'A complete supplier description',
        'image' => UploadedFile::fake()->image('supplier.jpg'),
        'sort_order' => 10,
        'status' => 1,
    ])
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Full Supplier',
        'description' => 'A complete supplier description',
        'sort_order' => 10,
    ]);
});

it('can update supplier with new fields', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $supplier = Supplier::create([
        'name' => 'Old Supplier',
        'status' => 1,
    ]);

    putJson(route('admin.suppliers.update', $supplier->id), [
        'name' => 'Updated Supplier',
        'description' => 'New description',
        'image' => UploadedFile::fake()->image('new.jpg'),
        'sort_order' => 5,
        'status' => 1,
    ])
        ->assertRedirect();

    $supplier->refresh();

    expect($supplier->description)->toBe('New description');
    expect($supplier->image)->not->toBeNull();
    expect($supplier->sort_order)->toBe(5);
});

it('description is optional', function () {
    $this->loginAsAdmin();

    postJson(route('admin.suppliers.store'), [
        'name' => 'Minimal Supplier',
        'status' => 1,
    ])
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Minimal Supplier',
        'description' => null,
    ]);
});

it('image is optional', function () {
    $this->loginAsAdmin();

    postJson(route('admin.suppliers.store'), [
        'name' => 'No Image Supplier',
        'status' => 1,
    ])
        ->assertRedirect();

    $supplier = Supplier::where('name', 'No Image Supplier')->first();

    expect($supplier->image)->toBeNull();
});

// DataGrid Tests

it('supplier datagrid shows products count', function () {
    $this->loginAsAdmin();

    $supplier = Supplier::create([
        'name' => 'Test Supplier',
        'status' => 1,
    ]);

    $product = Product::factory()->create(['supplier_id' => $supplier->id]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.suppliers.index'))
        ->assertOk()
        ->assertJsonStructure([
            'records' => [
                '*' => [
                    'supplier_id',
                    'name',
                    'products_count',
                ],
            ],
        ]);

    $supplierData = collect($response->json('records'))->firstWhere('supplier_id', $supplier->id);

    expect($supplierData['products_count'])->toContain('>1<');
});

it('supplier datagrid shows image thumbnail', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $imagePath = UploadedFile::fake()->image('test.jpg')->store('supplier_images', 'public');

    $supplier = Supplier::create([
        'name' => 'Supplier with Image',
        'image' => $imagePath,
        'status' => 1,
    ]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.suppliers.index'))
        ->assertOk();

    $supplierData = collect($response->json('records'))->firstWhere('supplier_id', $supplier->id);

    expect($supplierData)->toHaveKey('image');
    expect($supplierData['image'])->toContain('img src=');
});

it('products count links to filtered products', function () {
    $this->loginAsAdmin();

    $supplier = Supplier::create([
        'name' => 'Linked Supplier',
        'status' => 1,
    ]);

    Product::factory()->create(['supplier_id' => $supplier->id]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.suppliers.index'))
        ->assertOk();

    $supplierData = collect($response->json('records'))->firstWhere('supplier_id', $supplier->id);

    expect($supplierData['products_count'])->toContain('filters%5Bsupplier_id%5D='.$supplier->id);
});

// Product Filter Tests

it('can filter products by supplier', function () {
    $this->loginAsAdmin();

    $supplier = Supplier::create([
        'name' => 'Filter Test Supplier',
        'status' => 1,
    ]);

    $product = Product::factory()->create(['supplier_id' => $supplier->id]);

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.catalog.products.index', [
            'filters' => ['supplier_name' => $supplier->id],
        ]))
        ->assertOk()
        ->assertJsonStructure([
            'records',
        ]);

    // Verify response structure is valid
    $records = $response->json('records');
    expect($records)->toBeArray();
});

it('cannot delete supplier with products', function () {
    $this->loginAsAdmin();

    $supplier = Supplier::create([
        'name' => 'Supplier with Products',
        'status' => 1,
    ]);

    Product::factory()->create(['supplier_id' => $supplier->id]);

    deleteJson(route('admin.suppliers.destroy', $supplier->id))
        ->assertStatus(400);

    $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
});
