<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Models\Import as DataTransferImport;
use Webkul\ImportExport\Http\Controllers\Admin\Catalog\ImportController;
use Webkul\ImportExport\Models\CatalogImportLogEntry;
use Webkul\ImportExport\Models\CatalogImportSession;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Supplier\Models\Supplier;
use Webkul\User\Models\Admin;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

it('should return catalog imports index page', function () {
    $this->loginAsAdmin();

    get(route('admin.catalog.imports.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.imports.index.title'))
        ->assertSee('document.addEventListener', false);
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

it('should reject csv upload with non-utf8 encoding', function () {
    $this->loginAsAdmin();

    Storage::fake('private');

    $initialSessionsCount = CatalogImportSession::count();

    $csvContent = mb_convert_encoding("sku,name,price\nSKU001,Товар,100\n", 'Windows-1251', 'UTF-8');
    $file = UploadedFile::fake()->createWithContent('products.csv', $csvContent);

    post(route('admin.catalog.imports.store'), [
        'file' => $file,
        'delimiter' => 'comma',
        'locale' => 'en',
    ])->assertSessionHasErrors('file');

    expect(CatalogImportSession::count())->toBe($initialSessionsCount);
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

    $response->assertRedirect();

    $session = CatalogImportSession::whereNotNull('id')->latest('id')->first();

    expect($session)->not->toBeNull()
        ->and($session->headers)->toBe(['sku', 'name', 'price'])
        ->and($session->locale)->toBe('en');
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

it('should show grouped mapping fields including categories and image urls', function () {
    $this->loginAsAdmin();

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name', 'categories', 'image_link'],
        'created_by' => 1,
    ]);

    get(route('admin.catalog.imports.show', $session->id))
        ->assertOk()
        ->assertViewHas('bagistoFields', function (array $fields): bool {
            $codes = [];

            foreach ($fields as $group) {
                if (! is_array($group) || ! isset($group['children']) || ! is_array($group['children'])) {
                    return false;
                }

                foreach ($group['children'] as $field) {
                    if (! isset($field['code'])) {
                        return false;
                    }

                    $codes[] = $field['code'];
                }
            }

            return in_array('__skip__', $codes, true)
                && in_array('categories', $codes, true)
                && in_array('image_url', $codes, true);
        });
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

it('should return completed import summary from datatransfer import record', function () {
    $admin = $this->loginAsAdmin();

    $import = DataTransferImport::create([
        'type' => 'products',
        'state' => ImportHelper::STATE_COMPLETED,
        'file_path' => 'imports/products.csv',
        'action' => 'append',
        'validation_strategy' => 'skip-errors',
        'allowed_errors' => 100,
        'field_separator' => ',',
        'summary' => [
            'created' => 2,
            'updated' => 1,
            'deleted' => 0,
        ],
    ]);

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PROCESSING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name'],
        'created_by' => $admin->id,
        'import_ref_id' => $import->id,
    ]);

    get(route('admin.catalog.imports.status', $session->id))
        ->assertOk()
        ->assertJsonPath('state', CatalogImportSession::STATE_COMPLETED)
        ->assertJsonPath('stats.progress', 100)
        ->assertJsonPath('stats.summary.created', 2)
        ->assertJsonPath('stats.summary.updated', 1)
        ->assertJsonPath('stats.summary.deleted', 0);

    expect($session->fresh()->state)->toBe(CatalogImportSession::STATE_COMPLETED)
        ->and($session->fresh()->completed_at)->not->toBeNull();
});

it('should delete own catalog import session and storage file', function () {
    $admin = $this->loginAsAdmin();

    Storage::fake('private');
    Storage::disk('private')->put('catalog-imports/test.csv', "sku\n");

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku'],
        'created_by' => $admin->id,
    ]);

    deleteJson(route('admin.catalog.imports.delete', $session->id))
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.catalog.imports.index.delete-success'));

    expect(CatalogImportSession::find($session->id))->toBeNull()
        ->and(Storage::disk('private')->exists('catalog-imports/test.csv'))->toBeFalse();
});

it('should return 404 when deleting another admins catalog import session', function () {
    $owner = Admin::factory()->create();

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/other.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku'],
        'created_by' => $owner->id,
    ]);

    $this->loginAsAdmin();

    deleteJson(route('admin.catalog.imports.delete', $session->id))
        ->assertNotFound();

    expect(CatalogImportSession::find($session->id))->not->toBeNull();
});

it('should return 422 when deleting a processing catalog import session', function () {
    $admin = $this->loginAsAdmin();

    Storage::fake('private');
    Storage::disk('private')->put('catalog-imports/proc.csv', "sku\n");

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PROCESSING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/proc.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku'],
        'created_by' => $admin->id,
    ]);

    deleteJson(route('admin.catalog.imports.delete', $session->id))
        ->assertStatus(422)
        ->assertJsonPath('message', trans('admin::app.catalog.imports.index.delete-processing-not-allowed'));

    expect(CatalogImportSession::find($session->id))->not->toBeNull();
});

it('should mass delete own catalog import sessions', function () {
    $admin = $this->loginAsAdmin();

    Storage::fake('private');
    Storage::disk('private')->put('catalog-imports/a.csv', "sku\n");
    Storage::disk('private')->put('catalog-imports/b.csv', "sku\n");

    $sessionA = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_COMPLETED,
        'file_name' => 'a.csv',
        'file_path' => 'catalog-imports/a.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku'],
        'created_by' => $admin->id,
    ]);

    $sessionB = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_FAILED,
        'file_name' => 'b.csv',
        'file_path' => 'catalog-imports/b.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku'],
        'created_by' => $admin->id,
    ]);

    postJson(route('admin.catalog.imports.mass_delete'), [
        'indices' => [$sessionA->id, $sessionB->id],
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.catalog.imports.index.mass-delete-success'));

    expect(CatalogImportSession::query()->whereIn('id', [$sessionA->id, $sessionB->id])->count())->toBe(0)
        ->and(Storage::disk('private')->exists('catalog-imports/a.csv'))->toBeFalse()
        ->and(Storage::disk('private')->exists('catalog-imports/b.csv'))->toBeFalse();
});

it('should pass inventory sources to the show view', function () {
    $this->loginAsAdmin();

    $inventorySource = InventorySource::factory()->create(['status' => 1]);

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/inv_show_test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name'],
        'created_by' => 1,
    ]);

    get(route('admin.catalog.imports.show', $session->id))
        ->assertOk()
        ->assertViewHas('inventorySources', function ($sources) use ($inventorySource): bool {
            return $sources->contains('id', $inventorySource->id);
        });
});

it('should save inventory_source_id when starting import', function () {
    $admin = $this->loginAsAdmin();

    Storage::fake('private');

    $inventorySource = InventorySource::factory()->create(['status' => 1]);

    Storage::disk('private')->put('catalog-imports/inv_start_test.csv', "sku,name\nSKU001,Test\n");

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/inv_start_test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name'],
        'created_by' => $admin->id,
    ]);

    postJson(route('admin.catalog.imports.start', $session->id), [
        'column_mapping' => ['sku' => 'sku', 'name' => 'name'],
        'inventory_source_id' => $inventorySource->id,
    ]);

    expect($session->fresh()->inventory_source_id)->toBe($inventorySource->id);
});

it('should reject non-existent inventory_source_id when starting import', function () {
    $admin = $this->loginAsAdmin();

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PENDING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/inv_invalid_test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku', 'name'],
        'created_by' => $admin->id,
    ]);

    postJson(route('admin.catalog.imports.start', $session->id), [
        'column_mapping' => ['sku' => 'sku', 'name' => 'name'],
        'inventory_source_id' => 999999,
    ])->assertStatus(422);
});

it('can create and read a catalog import log entry', function () {
    $admin = Admin::factory()->create();

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_PROCESSING,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/test.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'headers' => ['sku'],
        'created_by' => $admin->id,
    ]);

    $entry = CatalogImportLogEntry::create([
        'session_id' => $session->id,
        'level' => 'info',
        'entity_type' => 'category',
        'action' => 'created',
        'entity_id' => 42,
        'message' => 'Electronics',
    ]);

    expect($entry->id)->toBeInt()
        ->and($entry->session_id)->toBe($session->id)
        ->and($entry->action)->toBe('created')
        ->and($entry->created_at)->not->toBeNull();

    // FK cascade: deleting session removes log entries
    $session->delete();
    expect(CatalogImportLogEntry::find($entry->id))->toBeNull();
});

it('should apply source_code=qty format when inventories column mapped directly with a warehouse', function () {
    $admin = $this->loginAsAdmin();

    Storage::fake('private');

    $inventorySource = InventorySource::factory()->create(['status' => 1, 'code' => 'main']);

    Storage::disk('private')->put(
        'catalog-imports/inv_direct_remap.csv',
        "sku,stock\nSKU001,25\n"
    );

    $session = CatalogImportSession::create([
        'state' => CatalogImportSession::STATE_READY,
        'file_name' => 'products.csv',
        'file_path' => 'catalog-imports/inv_direct_remap.csv',
        'delimiter' => ',',
        'locale' => 'en',
        'inventory_source_id' => $inventorySource->id,
        'column_mapping' => ['sku' => 'sku', 'stock' => 'inventories'],
        'headers' => ['sku', 'stock'],
        'allow_insert' => true,
        'allow_update' => true,
        'created_by' => $admin->id,
    ]);

    $controller = app(ImportController::class);

    $method = new ReflectionMethod($controller, 'createRemappedCsv');

    $remappedPath = $method->invoke($controller, $session);

    expect($remappedPath)->not->toBeNull();

    $remappedContent = Storage::disk('private')->get($remappedPath);

    expect($remappedContent)->not->toBeNull();

    $lines = array_filter(explode("\n", trim($remappedContent)));
    $headers = str_getcsv(array_values($lines)[0]);
    $row = str_getcsv(array_values($lines)[1]);
    $data = array_combine($headers, $row);

    expect($data['inventories'])->toBe('main=25');
});

it('resolveImportSuppliers creates new suppliers and marks existing ones as found', function () {
    $this->loginAsAdmin();

    Storage::fake('private');

    $existing = Supplier::create(['name' => 'ExistingCo', 'status' => true]);

    Storage::disk('private')->put(
        'catalog-imports/sup_test.csv',
        "sku,vendor\nSKU001,ExistingCo\nSKU002,NewVendorXYZ\n"
    );

    $session = CatalogImportSession::create([
        'state'          => CatalogImportSession::STATE_READY,
        'file_name'      => 'sup_test.csv',
        'file_path'      => 'catalog-imports/sup_test.csv',
        'delimiter'      => ',',
        'locale'         => 'en',
        'column_mapping' => ['sku' => 'sku', 'vendor' => 'supplier'],
        'headers'        => ['sku', 'vendor'],
        'created_by'     => 1,
    ]);

    $controller = app(ImportController::class);
    $method     = new ReflectionMethod($controller, 'resolveImportSuppliers');

    $result = $method->invoke($controller, $session);

    expect($result)->toHaveKey('map')
        ->and($result)->toHaveKey('events')
        ->and($result['events'])->toHaveCount(2);

    $actions = array_column($result['events'], 'action');

    expect(in_array('found', $actions, true))->toBeTrue()
        ->and(in_array('created', $actions, true))->toBeTrue();

    $newSupplier = Supplier::where('name', 'NewVendorXYZ')->first();
    expect($newSupplier)->not->toBeNull();
});

it('createMissingCategories returns array of created category events', function () {
    $this->loginAsAdmin();

    Storage::fake('private');

    $anchorId = (int) core()->getDefaultChannel()->root_category_id;
    $suffix   = uniqid('logcat_');

    Storage::disk('private')->put(
        'catalog-imports/cats_test.csv',
        "sku,categories\nSKU001,TestBrand{$suffix}\n"
    );

    $session = CatalogImportSession::create([
        'state'              => CatalogImportSession::STATE_READY,
        'file_name'          => 'cats_test.csv',
        'file_path'          => 'catalog-imports/cats_test.csv',
        'delimiter'          => ',',
        'locale'             => 'en',
        'column_mapping'     => ['sku' => 'sku', 'categories' => 'categories'],
        'headers'            => ['sku', 'categories'],
        'parent_category_id' => $anchorId,
        'create_categories'  => true,
        'created_by'         => 1,
    ]);

    $controller = app(ImportController::class);
    $method     = new ReflectionMethod($controller, 'createMissingCategories');

    $events = $method->invoke($controller, $session);

    expect($events)->toBeArray()
        ->and(count($events))->toBe(1)
        ->and($events[0])->toHaveKey('id')
        ->and($events[0])->toHaveKey('name')
        ->and($events[0]['name'])->toBe("TestBrand{$suffix}");
});
