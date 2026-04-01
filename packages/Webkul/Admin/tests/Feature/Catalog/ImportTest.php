<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Models\CatalogImportSession;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('should return catalog imports index page', function () {
    $this->loginAsAdmin();

    get(route('admin.catalog.imports.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.imports.index.title'));
});

it('should return the create/upload page', function () {
    $this->loginAsAdmin();

    get(route('admin.catalog.imports.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.imports.upload.title'));
});

it('should reject upload when no file is provided', function () {
    $this->loginAsAdmin();

    post(route('admin.catalog.imports.store'), [
        'delimiter' => 'comma',
        'locale' => 'en',
    ])->assertSessionHasErrors('file');
});

it('should reject non-csv file upload', function () {
    $this->loginAsAdmin();

    Storage::fake('private');

    post(route('admin.catalog.imports.store'), [
        'file' => UploadedFile::fake()->create('test.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        'delimiter' => 'comma',
        'locale' => 'en',
    ])->assertSessionHasErrors('file');
});

it('should upload csv and create import session', function () {
    $this->loginAsAdmin();

    Storage::fake('private');

    $csvContent = "sku,name,price\nSKU001,Test Product,100\n";
    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    $response = post(route('admin.catalog.imports.store'), [
        'file' => $file,
        'delimiter' => 'comma',
        'locale' => 'en',
    ]);

    $session = CatalogImportSession::latest()->first();

    expect($session)->not->toBeNull()
        ->and($session->state)->toBe(CatalogImportSession::STATE_PENDING)
        ->and($session->headers)->toBe(['sku', 'name', 'price'])
        ->and($session->locale)->toBe('en');

    $response->assertRedirect(route('admin.catalog.imports.show', $session->id));
});

it('should return show page for an existing session', function () {
    $this->loginAsAdmin();

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name', 'price'],
        'created_by' => 1,
    ]);

    get(route('admin.catalog.imports.show', $session->id))
        ->assertOk();
});

it('should return 422 when starting import without mapping', function () {
    $this->loginAsAdmin();

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name'],
        'created_by' => 1,
    ]);

    post(route('admin.catalog.imports.start', $session->id))
        ->assertStatus(422);
});

it('should return status json for an import session', function () {
    $this->loginAsAdmin();

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_COMPLETED,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name'],
        'created_by' => 1,
    ]);

    get(route('admin.catalog.imports.status', $session->id))
        ->assertOk()
        ->assertJsonPath('state', CatalogImportSession::STATE_COMPLETED);
});
