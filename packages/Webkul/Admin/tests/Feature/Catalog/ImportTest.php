<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Models\CatalogImportSession;
use Webkul\User\Models\Admin;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

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
