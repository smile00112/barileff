# Import Activity Log Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a real-time, per-entity activity log to the catalog import show page that records category creations, supplier resolutions, and per-product-ID create/update events, plus surfaces runtime errors.

**Architecture:** A new `catalog_import_log_entries` table (FK → `catalog_import_sessions`) stores immutable log rows. Category and supplier events are written synchronously in `ImportController::start()`. Product events come from a string-named Laravel event (`catalog_import.products_saved`) fired in the DataTransfer `Product\Importer::saveProducts()` and received by a listener in the ImportExport package. The `status()` endpoint streams new entries via an `after_log_id` query param; the show page polls and appends them in real time.

**Tech Stack:** Laravel 11, PHP 8.2, Pest 3, Vue 3 (embedded in Blade), PostgreSQL/MySQL

---

## File Map

| File | Change |
|---|---|
| `packages/Webkul/ImportExport/src/Database/Migrations/2026_04_19_000000_create_catalog_import_log_entries_table.php` | **Create** — new migration |
| `packages/Webkul/ImportExport/src/Models/CatalogImportLogEntry.php` | **Create** — new model |
| `packages/Webkul/ImportExport/src/Listeners/ProductsBatchSavedListener.php` | **Create** — handles `catalog_import.products_saved` event |
| `packages/Webkul/ImportExport/src/Providers/ImportExportServiceProvider.php` | **Modify** — register event listener |
| `packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php` | **Modify** — `createMissingCategories()` return type, new `resolveImportSuppliers()`, `createRemappedCsv()` signature, `start()` log writing, `show()` initial log, `status()` streaming |
| `packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php` | **Modify** — fire event in `saveProducts()` |
| `packages/Webkul/ImportExport/src/Resources/views/admin/catalog/imports/show.blade.php` | **Modify** — add log panel + errors panel |
| `packages/Webkul/Admin/src/Resources/lang/en/app.php` | **Modify** — add `catalog.imports.log.*` keys |
| `packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php` | **Modify** — add new test cases |

---

## Task 1: Migration and Model

**Files:**
- Create: `packages/Webkul/ImportExport/src/Database/Migrations/2026_04_19_000000_create_catalog_import_log_entries_table.php`
- Create: `packages/Webkul/ImportExport/src/Models/CatalogImportLogEntry.php`

- [ ] **Step 1: Write the failing test**

Add to `packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php`:

```php
use Webkul\ImportExport\Models\CatalogImportLogEntry;

it('can create and read a catalog import log entry', function () {
    $admin = Admin::factory()->create();

    $session = CatalogImportSession::create([
        'state'      => CatalogImportSession::STATE_PROCESSING,
        'file_name'  => 'products.csv',
        'file_path'  => 'catalog-imports/test.csv',
        'delimiter'  => ',',
        'locale'     => 'en',
        'headers'    => ['sku'],
        'created_by' => $admin->id,
    ]);

    $entry = CatalogImportLogEntry::create([
        'session_id'  => $session->id,
        'level'       => 'info',
        'entity_type' => 'category',
        'action'      => 'created',
        'entity_id'   => 42,
        'message'     => 'Electronics',
    ]);

    expect($entry->id)->toBeInt()
        ->and($entry->session_id)->toBe($session->id)
        ->and($entry->action)->toBe('created')
        ->and($entry->created_at)->not->toBeNull();

    // FK cascade: deleting session removes log entries
    $session->delete();
    expect(CatalogImportLogEntry::find($entry->id))->toBeNull();
});
```

- [ ] **Step 2: Run to verify it fails**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="can create and read"
```

Expected: FAIL — `CatalogImportLogEntry` class not found.

- [ ] **Step 3: Create the migration**

```php
<?php
// packages/Webkul/ImportExport/src/Database/Migrations/2026_04_19_000000_create_catalog_import_log_entries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_import_log_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('level', 16)->default('info');
            $table->string('entity_type', 32);
            $table->string('action', 32);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['session_id', 'id']);

            $table->foreign('session_id')
                ->references('id')
                ->on('catalog_import_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_import_log_entries');
    }
};
```

- [ ] **Step 4: Create the model**

```php
<?php
// packages/Webkul/ImportExport/src/Models/CatalogImportLogEntry.php

namespace Webkul\ImportExport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogImportLogEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'level',
        'entity_type',
        'action',
        'entity_id',
        'message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'entity_id'  => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CatalogImportSession::class, 'session_id');
    }
}
```

- [ ] **Step 5: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 6: Run the test**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="can create and read"
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add packages/Webkul/ImportExport/src/Database/Migrations/2026_04_19_000000_create_catalog_import_log_entries_table.php \
        packages/Webkul/ImportExport/src/Models/CatalogImportLogEntry.php \
        packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
git commit -m "feat(import-log): add catalog_import_log_entries migration and model"
```

---

## Task 2: Modify `createMissingCategories()` to Return Created Events

**Files:**
- Modify: `packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php`

- [ ] **Step 1: Write the failing test**

Add to `ImportTest.php`:

```php
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
        'state'             => CatalogImportSession::STATE_READY,
        'file_name'         => 'cats_test.csv',
        'file_path'         => 'catalog-imports/cats_test.csv',
        'delimiter'         => ',',
        'locale'            => 'en',
        'column_mapping'    => ['sku' => 'sku', 'categories' => 'categories'],
        'headers'           => ['sku', 'categories'],
        'parent_category_id' => $anchorId,
        'create_categories' => true,
        'created_by'        => 1,
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
```

- [ ] **Step 2: Run to verify it fails**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="createMissingCategories returns"
```

Expected: FAIL — method returns `null` (void).

- [ ] **Step 3: Update `createMissingCategories()` in the controller**

Find the method in `ImportController.php` (around line 677). Replace it entirely:

```php
/**
 * Create missing categories referenced in the import file.
 *
 * Returns an array of ['id' => int, 'name' => string] for each category that
 * was newly created (pre-existing ones are not included).
 *
 * @return array<int, array{id: int, name: string}>
 */
protected function createMissingCategories(CatalogImportSession $session): array
{
    $mapping = $session->column_mapping ?? [];
    $originalPath = Storage::disk('private')->path($session->file_path);
    $delimiter = $session->delimiter;
    $anchorId  = (int) ($session->parent_category_id ?? 1);
    $locale    = $session->locale;

    $categoriesHeader = null;

    foreach ($mapping as $header => $field) {
        if ($field === 'categories') {
            $categoriesHeader = $header;
            break;
        }
    }

    if ($categoriesHeader === null) {
        return [];
    }

    $handle = fopen($originalPath, 'r');

    if (! $handle) {
        return [];
    }

    $originalHeaders     = fgetcsv($handle, 4096, $delimiter) ?: [];
    $categoriesColIndex  = array_search($categoriesHeader, $originalHeaders, true);

    if ($categoriesColIndex === false) {
        fclose($handle);

        return [];
    }

    // Snapshot of existing category IDs before we create anything.
    $preExistingIds  = DB::table('categories')->pluck('id', 'id')->all();
    $allReturnedIds  = [];
    $seenChains      = [];

    while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
        $cell = trim($row[$categoriesColIndex] ?? '');

        if ($cell === '') {
            continue;
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $cell))));

        if ($segments === []) {
            continue;
        }

        $chainKey = implode("\0", $segments);

        if (isset($seenChains[$chainKey])) {
            continue;
        }

        $seenChains[$chainKey] = true;

        $returnedIds = $this->categoryRepository->ensureCategoryChainUnderParent($anchorId, $segments, $locale);

        foreach ($returnedIds as $catId) {
            $allReturnedIds[$catId] = true;
        }
    }

    fclose($handle);

    // New categories = returned IDs that were not in the pre-existing snapshot.
    $newCategoryIds = array_keys(array_diff_key($allReturnedIds, $preExistingIds));

    if (empty($newCategoryIds)) {
        return [];
    }

    // Batch-fetch their translated names.
    $categoryNames = DB::table('category_translations')
        ->whereIn('category_id', $newCategoryIds)
        ->where('locale', $locale)
        ->pluck('name', 'category_id')
        ->all();

    return array_map(
        fn (int $id): array => [
            'id'   => $id,
            'name' => $categoryNames[$id] ?? "Category #{$id}",
        ],
        $newCategoryIds
    );
}
```

- [ ] **Step 4: Run the test**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="createMissingCategories returns"
```

Expected: PASS.

- [ ] **Step 5: Run the full suite to check nothing broke**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
```

Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php \
        packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
git commit -m "feat(import-log): createMissingCategories returns created category events"
```

---

## Task 3: Extract `resolveImportSuppliers()` and Update `createRemappedCsv()` Signature

**Files:**
- Modify: `packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php`

- [ ] **Step 1: Write the failing test**

Add to `ImportTest.php`:

```php
use Webkul\Supplier\Models\Supplier;

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
```

- [ ] **Step 2: Run to verify it fails**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="resolveImportSuppliers"
```

Expected: FAIL — method not found.

- [ ] **Step 3: Add `resolveImportSuppliers()` to `ImportController`**

Insert this new protected method just before `createRemappedCsv()` in `ImportController.php`:

```php
/**
 * Scan the import CSV for supplier names, create missing ones, and return the
 * resolved name→id map plus per-supplier log events.
 *
 * @return array{map: array<string, int>, events: array<int, array{action: string, id: int, name: string}>}
 */
protected function resolveImportSuppliers(CatalogImportSession $session): array
{
    $mapping = $session->column_mapping ?? [];

    if (! in_array('supplier', $mapping, true)) {
        return ['map' => [], 'events' => []];
    }

    $originalPath = Storage::disk('private')->path($session->file_path);
    $delimiter    = $session->delimiter;

    $handle = fopen($originalPath, 'r');

    if (! $handle) {
        return ['map' => [], 'events' => []];
    }

    $originalHeaders  = fgetcsv($handle, 4096, $delimiter) ?: [];
    $supplierColIndex = null;

    foreach ($originalHeaders as $idx => $header) {
        if (($mapping[$header] ?? null) === 'supplier') {
            $supplierColIndex = $idx;
            break;
        }
    }

    if ($supplierColIndex === null) {
        fclose($handle);

        return ['map' => [], 'events' => []];
    }

    $uniqueNames = [];

    while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
        $name = trim($row[$supplierColIndex] ?? '');

        if ($name !== '') {
            $uniqueNames[$name] = true;
        }
    }

    fclose($handle);

    if (empty($uniqueNames)) {
        return ['map' => [], 'events' => []];
    }

    $existingMap = Supplier::all(['id', 'name'])
        ->mapWithKeys(fn (Supplier $s) => [mb_strtolower($s->name) => $s->id])
        ->all();

    $supplierNameToId = $existingMap;
    $events           = [];

    foreach (array_keys($uniqueNames) as $name) {
        $lower = mb_strtolower($name);

        if (isset($existingMap[$lower])) {
            $events[] = ['action' => 'found', 'id' => $existingMap[$lower], 'name' => $name];
        } else {
            $new = Supplier::create(['name' => $name, 'status' => true]);
            $supplierNameToId[$lower] = $new->id;
            $events[] = ['action' => 'created', 'id' => $new->id, 'name' => $name];
        }
    }

    return ['map' => $supplierNameToId, 'events' => $events];
}
```

- [ ] **Step 4: Update `createRemappedCsv()` signature to accept pre-resolved map**

Change the method signature from:

```php
protected function createRemappedCsv(CatalogImportSession $session): ?string
```

to:

```php
protected function createRemappedCsv(CatalogImportSession $session, array $supplierNameToId = []): ?string
```

Then remove the entire supplier pre-scan block from inside `createRemappedCsv`. That block starts at the comment `// Pre-scan the CSV to resolve supplier names → IDs` and ends at the comment `// Rewind to data rows for the main processing loop below.` (including the two `rewind`/`fgetcsv` calls that follow). The `$supplierNameToId` variable is now provided by the parameter.

The section to remove looks like this (lines ~537–574):

```php
        // Pre-scan the CSV to resolve supplier names → IDs (create missing suppliers).
        // Uses a second pass over the original file so we can bulk-create in one go.
        $supplierNameToId = [];

        if ($supplierColumnIndex !== null) {
            rewind($handle);
            fgetcsv($handle, 4096, $delimiter); // skip header row

            $uniqueSupplierNames = [];

            while (($scanRow = fgetcsv($handle, 4096, $delimiter)) !== false) {
                $name = trim($scanRow[$supplierColumnIndex] ?? '');

                if ($name !== '') {
                    $uniqueSupplierNames[$name] = true;
                }
            }

            if (! empty($uniqueSupplierNames)) {
                // Build a lowercase-name → id map from all existing suppliers.
                $supplierNameToId = Supplier::all(['id', 'name'])
                    ->mapWithKeys(fn (Supplier $s) => [mb_strtolower($s->name) => $s->id])
                    ->all();

                // Create any suppliers that are not yet in the database.
                foreach (array_keys($uniqueSupplierNames) as $name) {
                    $lower = mb_strtolower($name);

                    if (! isset($supplierNameToId[$lower])) {
                        $newSupplier = Supplier::create(['name' => $name, 'status' => true]);
                        $supplierNameToId[$lower] = $newSupplier->id;
                    }
                }
            }

            // Rewind to data rows for the main processing loop below.
            rewind($handle);
            fgetcsv($handle, 4096, $delimiter); // skip header row
        }
```

Remove this entire block. The `$supplierNameToId` variable in the row-level resolution loop further below (`if ($field === 'supplier_id' && $supplierColumnIndex !== null)`) now refers to the method parameter.

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
```

Expected: all pass (including the existing `createRemappedCsv` test which still works since the new param defaults to `[]`).

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php \
        packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
git commit -m "feat(import-log): extract resolveImportSuppliers, update createRemappedCsv signature"
```

---

## Task 4: Write Log Entries in `start()`

**Files:**
- Modify: `packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php`

- [ ] **Step 1: Write the failing test**

Add to `ImportTest.php`:

```php
use Webkul\ImportExport\Models\CatalogImportLogEntry;

it('start writes category and supplier log entries', function () {
    $admin    = $this->loginAsAdmin();
    $anchorId = (int) core()->getDefaultChannel()->root_category_id;
    $suffix   = uniqid('startlog_');

    Storage::fake('private');

    $existing = Supplier::create(['name' => 'ExistingBrand', 'status' => true]);

    Storage::disk('private')->put(
        "catalog-imports/start_log_{$suffix}.csv",
        "sku,categories,vendor\nSKU001,Cat{$suffix},ExistingBrand\nSKU002,Cat{$suffix},NewBrand{$suffix}\n"
    );

    $session = CatalogImportSession::create([
        'state'              => CatalogImportSession::STATE_PENDING,
        'file_name'          => "start_log_{$suffix}.csv",
        'file_path'          => "catalog-imports/start_log_{$suffix}.csv",
        'delimiter'          => ',',
        'locale'             => 'en',
        'headers'            => ['sku', 'categories', 'vendor'],
        'parent_category_id' => $anchorId,
        'create_categories'  => true,
        'created_by'         => $admin->id,
    ]);

    postJson(route('admin.catalog.imports.start', $session->id), [
        'column_mapping' => [
            'sku'        => 'sku',
            'categories' => 'categories',
            'vendor'     => 'supplier',
        ],
    ]);

    // Check category log entry
    $catEntries = CatalogImportLogEntry::where('session_id', $session->id)
        ->where('entity_type', 'category')
        ->where('action', 'created')
        ->get();

    expect($catEntries->count())->toBeGreaterThanOrEqual(1);

    // Check supplier log entries
    $supEntries = CatalogImportLogEntry::where('session_id', $session->id)
        ->where('entity_type', 'supplier')
        ->get();

    expect($supEntries->count())->toBe(2);

    $actions = $supEntries->pluck('action')->all();
    expect(in_array('found', $actions, true))->toBeTrue()
        ->and(in_array('created', $actions, true))->toBeTrue();
});
```

- [ ] **Step 2: Run to verify it fails**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="start writes category and supplier"
```

Expected: FAIL — no log entries created.

- [ ] **Step 3: Update `start()` in `ImportController`**

Add this import at the top of the file:

```php
use Webkul\ImportExport\Models\CatalogImportLogEntry;
```

Then in `start()`, replace:

```php
        if ($session->create_categories) {
            $this->createMissingCategories($session);
        }

        $remappedPath = $this->createRemappedCsv($session);
```

with:

```php
        $categoryEvents = $session->create_categories
            ? $this->createMissingCategories($session)
            : [];

        ['map' => $supplierNameToId, 'events' => $supplierEvents] = $this->resolveImportSuppliers($session);

        // Write category and supplier log entries now (before validation so they persist
        // even if the import fails later at the validation stage).
        $logRows = [];

        foreach ($categoryEvents as $event) {
            $logRows[] = [
                'session_id'  => $session->id,
                'level'       => 'info',
                'entity_type' => 'category',
                'action'      => 'created',
                'entity_id'   => $event['id'],
                'message'     => $event['name'],
            ];
        }

        foreach ($supplierEvents as $event) {
            $logRows[] = [
                'session_id'  => $session->id,
                'level'       => 'info',
                'entity_type' => 'supplier',
                'action'      => $event['action'],
                'entity_id'   => $event['id'],
                'message'     => $event['name'],
            ];
        }

        if ($logRows !== []) {
            CatalogImportLogEntry::insert($logRows);
        }

        $remappedPath = $this->createRemappedCsv($session, $supplierNameToId);
```

- [ ] **Step 4: Run the test**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="start writes category and supplier"
```

Expected: PASS.

- [ ] **Step 5: Run the full suite**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
```

Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php \
        packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
git commit -m "feat(import-log): write category and supplier log entries in start()"
```

---

## Task 5: Update `status()` and `show()` to Return/Pass Log Entries

**Files:**
- Modify: `packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php`

- [ ] **Step 1: Write the failing tests**

Add to `ImportTest.php`:

```php
it('status returns log_entries for the session', function () {
    $admin = $this->loginAsAdmin();

    $session = CatalogImportSession::create([
        'state'      => CatalogImportSession::STATE_COMPLETED,
        'file_name'  => 'products.csv',
        'file_path'  => 'catalog-imports/test.csv',
        'delimiter'  => ',',
        'locale'     => 'en',
        'headers'    => ['sku'],
        'created_by' => $admin->id,
    ]);

    CatalogImportLogEntry::insert([
        ['session_id' => $session->id, 'level' => 'info', 'entity_type' => 'category', 'action' => 'created', 'entity_id' => 5,  'message' => 'Electronics'],
        ['session_id' => $session->id, 'level' => 'info', 'entity_type' => 'supplier', 'action' => 'found',   'entity_id' => 10, 'message' => 'ACME'],
    ]);

    get(route('admin.catalog.imports.status', $session->id))
        ->assertOk()
        ->assertJsonCount(2, 'log_entries')
        ->assertJsonPath('log_entries.0.entity_type', 'category');
});

it('status filters log_entries by after_log_id', function () {
    $admin = $this->loginAsAdmin();

    $session = CatalogImportSession::create([
        'state'      => CatalogImportSession::STATE_COMPLETED,
        'file_name'  => 'products.csv',
        'file_path'  => 'catalog-imports/test.csv',
        'delimiter'  => ',',
        'locale'     => 'en',
        'headers'    => ['sku'],
        'created_by' => $admin->id,
    ]);

    $entry1 = CatalogImportLogEntry::create(['session_id' => $session->id, 'level' => 'info', 'entity_type' => 'category', 'action' => 'created', 'entity_id' => 5,  'message' => 'Electronics']);
    $entry2 = CatalogImportLogEntry::create(['session_id' => $session->id, 'level' => 'info', 'entity_type' => 'supplier', 'action' => 'found',   'entity_id' => 10, 'message' => 'ACME']);

    get(route('admin.catalog.imports.status', $session->id).'?after_log_id='.$entry1->id)
        ->assertOk()
        ->assertJsonCount(1, 'log_entries')
        ->assertJsonPath('log_entries.0.entity_type', 'supplier');
});
```

- [ ] **Step 2: Run to verify they fail**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="status returns log_entries|status filters log_entries"
```

Expected: FAIL — no `log_entries` key in response.

- [ ] **Step 3: Update `status()` in `ImportController`**

Replace the entire `status()` method body with:

```php
    public function status(int $id): JsonResponse
    {
        $session    = CatalogImportSession::findOrFail($id);
        $afterLogId = (int) request()->query('after_log_id', 0);

        $logEntries = CatalogImportLogEntry::where('session_id', $session->id)
            ->where('id', '>', $afterLogId)
            ->orderBy('id')
            ->limit(500)
            ->get(['id', 'level', 'entity_type', 'action', 'entity_id', 'message', 'created_at'])
            ->toArray();

        if ($session->state !== CatalogImportSession::STATE_PROCESSING || ! $session->import_ref_id) {
            return new JsonResponse([
                'state'       => $session->state,
                'stats'       => [
                    'progress' => $session->state === CatalogImportSession::STATE_COMPLETED ? 100 : 0,
                    'batches'  => ['total' => 0, 'completed' => 0, 'remaining' => 0],
                    'summary'  => ['created' => 0, 'updated' => 0, 'deleted' => 0],
                ],
                'log_entries' => $logEntries,
                'errors'      => [],
            ]);
        }

        $dtImport = $this->importRepository->find($session->import_ref_id);

        if (! $dtImport) {
            return new JsonResponse([
                'state'       => $session->state,
                'stats'       => ['progress' => 0],
                'log_entries' => $logEntries,
                'errors'      => [],
            ]);
        }

        $this->importHelper->setImport($dtImport);

        $statsState = match ($dtImport->state) {
            ImportHelper::STATE_INDEXED,
            ImportHelper::STATE_COMPLETED => ImportHelper::STATE_INDEXED,
            ImportHelper::STATE_LINKED,
            ImportHelper::STATE_INDEXING,
            ImportHelper::STATE_LINKING   => ImportHelper::STATE_LINKED,
            default                       => ImportHelper::STATE_PROCESSED,
        };

        $stats  = $this->importHelper->stats($statsState);
        $errors = array_values($dtImport->errors ?? []);

        if ($dtImport->state === ImportHelper::STATE_COMPLETED) {
            $stats['progress'] = 100;
            $stats['summary']  = array_merge(
                ['created' => 0, 'updated' => 0, 'deleted' => 0],
                $dtImport->summary ?? $stats['summary'] ?? []
            );

            $session->update([
                'state'        => CatalogImportSession::STATE_COMPLETED,
                'completed_at' => now(),
            ]);

            $admin = Admin::find($session->created_by);

            activity('catalog_import')
                ->causedBy($admin)
                ->performedOn($session->fresh())
                ->withProperties($dtImport->summary ?? [])
                ->log('completed');

            Event::dispatch(new CatalogImportCompleted($session->fresh()));
        }

        return new JsonResponse([
            'state'        => $session->fresh()->state,
            'stats'        => $stats,
            'import_state' => $dtImport->state,
            'log_entries'  => $logEntries,
            'errors'       => $errors,
        ]);
    }
```

- [ ] **Step 4: Update `show()` to pass initial log entries and errors**

In the `show()` method, after the existing variables are built, add:

```php
        $initialLogEntries = CatalogImportLogEntry::where('session_id', $session->id)
            ->orderBy('id')
            ->get(['id', 'level', 'entity_type', 'action', 'entity_id', 'message', 'created_at'])
            ->toArray();

        $dtImportErrors = [];

        if ($session->import_ref_id) {
            $dtImport = $this->importRepository->find($session->import_ref_id);

            if ($dtImport && $dtImport->errors) {
                $dtImportErrors = array_values($dtImport->errors);
            }
        }
```

Update the `return view(...)` call to include the new variables:

```php
        return view('import_export::admin.catalog.imports.show', compact(
            'session',
            'bagistoFields',
            'inventorySources',
            'previewRows',
            'initialLogEntries',
            'dtImportErrors'
        ));
```

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
```

Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php \
        packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
git commit -m "feat(import-log): return log_entries and errors from status(); pass to show()"
```

---

## Task 6: Fire `catalog_import.products_saved` Event in DataTransfer Product Importer

**Files:**
- Modify: `packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php`

> **No separate test for this task.** The event dispatch is a one-line addition (`Event::dispatch(...)`) that is verified end-to-end by the Task 7 listener test: if the listener creates correct log entries when given the event payload, the wiring is correct. Manual verification also happens in Task 8 Step 6.

- [ ] **Step 2: Modify `saveProducts()` in `Importer.php`**

Replace the current `saveProducts()` method (around line 1059) with:

```php
    public function saveProducts(array $products): void
    {
        $updatedIds = [];

        if (! empty($products['update'])) {
            $this->updatedItemsCount += count($products['update']);

            foreach (array_keys($products['update']) as $sku) {
                $skuData = $this->skuStorage->get($sku);

                if ($skuData !== null) {
                    $updatedIds[] = (int) $skuData['id'];
                }
            }

            $this->productRepository->upsert(
                $products['update'],
                $this->masterAttributeCode
            );
        }

        $createdIds = [];

        if (! empty($products['insert'])) {
            $this->createdItemsCount += count($products['insert']);

            $this->productRepository->insert($products['insert']);

            /**
             * Update the sku storage with newly created products
             */
            $newProducts = $this->productRepository->findWhereIn(
                'sku',
                array_keys($products['insert']),
                [
                    'id',
                    'type',
                    'sku',
                    'attribute_family_id',
                ]
            );

            foreach ($newProducts as $product) {
                $createdIds[] = (int) $product->id;

                $this->skuStorage->set($product->sku, [
                    'id'                 => $product->id,
                    'type'               => $product->type,
                    'attribute_family_id' => $product->attribute_family_id,
                ]);
            }
        }

        if ($createdIds !== [] || $updatedIds !== []) {
            Event::dispatch('catalog_import.products_saved', [
                'import_id'   => $this->import->id,
                'created_ids' => $createdIds,
                'updated_ids' => $updatedIds,
            ]);
        }
    }
```

- [ ] **Step 3: Run existing DataTransfer-related tests**

```bash
php artisan test --compact --testsuite="Admin Feature Test" --filter="Product"
```

Expected: pass (no regressions).

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php \
        packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
git commit -m "feat(import-log): fire catalog_import.products_saved event in saveProducts()"
```

---

## Task 7: Implement `ProductsBatchSavedListener` and Register It

**Files:**
- Create: `packages/Webkul/ImportExport/src/Listeners/ProductsBatchSavedListener.php`
- Modify: `packages/Webkul/ImportExport/src/Providers/ImportExportServiceProvider.php`

- [ ] **Step 1: Write the failing test**

Add to `ImportTest.php`:

```php
use Webkul\ImportExport\Listeners\ProductsBatchSavedListener;

it('ProductsBatchSavedListener creates product log entries from event payload', function () {
    $admin = Admin::factory()->create();

    $session = CatalogImportSession::create([
        'state'         => CatalogImportSession::STATE_PROCESSING,
        'file_name'     => 'products.csv',
        'file_path'     => 'catalog-imports/test.csv',
        'delimiter'     => ',',
        'locale'        => 'en',
        'headers'       => ['sku'],
        'created_by'    => $admin->id,
        'import_ref_id' => 77777,
    ]);

    $listener = app(ProductsBatchSavedListener::class);

    $listener->handle([
        'import_id'   => 77777,
        'created_ids' => [101, 102],
        'updated_ids' => [200],
    ]);

    $entries = CatalogImportLogEntry::where('session_id', $session->id)
        ->orderBy('id')
        ->get();

    expect($entries)->toHaveCount(3)
        ->and($entries->where('action', 'created')->where('entity_type', 'product')->count())->toBe(2)
        ->and($entries->where('action', 'updated')->where('entity_type', 'product')->count())->toBe(1)
        ->and($entries->where('entity_id', 101)->first())->not->toBeNull()
        ->and($entries->where('entity_id', 200)->first()->action)->toBe('updated');
});

it('ProductsBatchSavedListener does nothing when no session matches import_id', function () {
    $before = CatalogImportLogEntry::count();

    $listener = app(ProductsBatchSavedListener::class);
    $listener->handle([
        'import_id'   => 999888777,
        'created_ids' => [1, 2, 3],
        'updated_ids' => [],
    ]);

    expect(CatalogImportLogEntry::count())->toBe($before);
});
```

- [ ] **Step 2: Run to verify they fail**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="ProductsBatchSavedListener"
```

Expected: FAIL — class not found.

- [ ] **Step 3: Create the listener**

```php
<?php
// packages/Webkul/ImportExport/src/Listeners/ProductsBatchSavedListener.php

namespace Webkul\ImportExport\Listeners;

use Webkul\ImportExport\Models\CatalogImportLogEntry;
use Webkul\ImportExport\Models\CatalogImportSession;

class ProductsBatchSavedListener
{
    public function handle(array $payload): void
    {
        $session = CatalogImportSession::where('import_ref_id', $payload['import_id'])->first();

        if (! $session) {
            return;
        }

        $rows = [];

        foreach ($payload['created_ids'] as $id) {
            $rows[] = [
                'session_id'  => $session->id,
                'level'       => 'info',
                'entity_type' => 'product',
                'action'      => 'created',
                'entity_id'   => $id,
                'message'     => null,
            ];
        }

        foreach ($payload['updated_ids'] as $id) {
            $rows[] = [
                'session_id'  => $session->id,
                'level'       => 'info',
                'entity_type' => 'product',
                'action'      => 'updated',
                'entity_id'   => $id,
                'message'     => null,
            ];
        }

        if (empty($rows)) {
            return;
        }

        // Chunk to avoid hitting DB parameter limits on large batches.
        foreach (array_chunk($rows, 500) as $chunk) {
            CatalogImportLogEntry::insert($chunk);
        }
    }
}
```

- [ ] **Step 4: Register the listener in `ImportExportServiceProvider`**

Replace the entire file with:

```php
<?php

namespace Webkul\ImportExport\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\ImportExport\Listeners\ProductsBatchSavedListener;

class ImportExportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'import_export');

        $this->mergeConfigFrom(__DIR__.'/../Config/admin-menu.php', 'menu.admin');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        Event::listen('catalog_import.products_saved', [ProductsBatchSavedListener::class, 'handle']);
    }
}
```

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php --filter="ProductsBatchSavedListener"
```

Expected: PASS.

- [ ] **Step 6: Run the full suite**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
```

Expected: all pass.

- [ ] **Step 7: Commit**

```bash
git add packages/Webkul/ImportExport/src/Listeners/ProductsBatchSavedListener.php \
        packages/Webkul/ImportExport/src/Providers/ImportExportServiceProvider.php \
        packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
git commit -m "feat(import-log): add ProductsBatchSavedListener and register catalog_import.products_saved"
```

---

## Task 8: Add Log Panel and Errors Panel to `show.blade.php`

**Files:**
- Modify: `packages/Webkul/ImportExport/src/Resources/views/admin/catalog/imports/show.blade.php`

- [ ] **Step 1: Add lang keys** (do this first so the template can reference them)

In `packages/Webkul/Admin/src/Resources/lang/en/app.php`, find the `'imports'` array (around line 1691) and add a `'log'` key after the `'errors'` key:

```php
            'log' => [
                'title'        => 'Activity Log',
                'empty'        => 'No activity yet.',
                'errors-title' => 'Import Errors',
            ],
```

- [ ] **Step 2: Add `initialLogEntries`, `dtImportErrors`, and `statusUrl` to Vue data**

In `show.blade.php`, find the `data()` block inside the Vue component (the `return { ... }` section). Add the new properties:

```js
                data() {
                    return {
                        state: '{{ $session->state }}',
                        headers: @json($session->headers ?? []),
                        mapping: @json((object)($session->column_mapping ?? [])),
                        bagistoFields: @json($bagistoFields),
                        inventorySources: @json($inventorySources),
                        inventorySourceId: {{ $session->inventory_source_id ?? 'null' }},
                        previewValues: @json((object)$previewRows),
                        isLoading: false,
                        error: null,
                        stats: {
                            progress: 0,
                            batches: { total: 0, completed: 0, remaining: 0 },
                            summary: { created: 0, updated: 0, deleted: 0 },
                        },
                        logEntries: @json($initialLogEntries),
                        lastLogId: {{ collect($initialLogEntries)->last()['id'] ?? 0 }},
                        errors: @json($dtImportErrors),
                        statusUrl: '{{ route('admin.catalog.imports.status', $session->id) }}',
                    };
                },
```

- [ ] **Step 3: Update `pollStatus()` to use `after_log_id` and handle new response fields**

Replace the `pollStatus()` method:

```js
                    pollStatus() {
                        this.$axios.get(this.statusUrl + '?after_log_id=' + this.lastLogId)
                            .then(response => {
                                this.state = response.data.state;

                                if (response.data.stats) {
                                    this.stats = response.data.stats;
                                }

                                if (response.data.log_entries && response.data.log_entries.length > 0) {
                                    this.appendLogEntries(response.data.log_entries);
                                }

                                if (response.data.errors && response.data.errors.length > 0) {
                                    this.errors = response.data.errors;
                                }

                                if (this.state === 'processing') {
                                    setTimeout(() => this.pollStatus(), 2000);
                                }
                            })
                            .catch(() => {
                                setTimeout(() => this.pollStatus(), 5000);
                            });
                    },
```

- [ ] **Step 4: Add `appendLogEntries()` and `formatLogEntry()` methods**

Add these two methods inside the `methods: { ... }` block (before the closing `},`):

```js
                    appendLogEntries(entries) {
                        const container = this.$refs.logContainer;
                        const isAtBottom = container
                            ? (container.scrollHeight - container.scrollTop - container.clientHeight < 48)
                            : true;

                        this.logEntries.push(...entries);
                        this.lastLogId = entries[entries.length - 1].id;

                        if (isAtBottom) {
                            this.$nextTick(() => {
                                if (container) {
                                    container.scrollTop = container.scrollHeight;
                                }
                            });
                        }
                    },

                    formatLogEntry(entry) {
                        if (entry.entity_type === 'product') {
                            return `Product #${entry.entity_id} ${entry.action}`;
                        }

                        if (entry.entity_type === 'category') {
                            return `Category "${entry.message}" (id=${entry.entity_id}) ${entry.action}`;
                        }

                        if (entry.entity_type === 'supplier') {
                            return `Supplier "${entry.message}" (id=${entry.entity_id}) ${entry.action}`;
                        }

                        return entry.message || `${entry.entity_type} #${entry.entity_id} ${entry.action}`;
                    },
```

- [ ] **Step 5: Add the Log Panel and Errors Panel to the template**

In the template, find the closing `</div>` of the outermost wrapper div (after Step 3b Failed panel). Add the two panels **before** that closing tag:

```html
                <!-- Activity Log Panel -->
                <div
                    v-if="logEntries.length > 0 || ['processing', 'completed', 'failed'].includes(state)"
                    class="box-shadow rounded-sm bg-white p-4 dark:bg-gray-900"
                >
                    <h3 class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.imports.log.title')
                    </h3>

                    <div
                        ref="logContainer"
                        class="max-h-80 overflow-y-auto rounded-sm border border-gray-100 bg-gray-50 p-3 font-mono text-xs dark:border-gray-800 dark:bg-gray-800"
                    >
                        <p
                            v-if="logEntries.length === 0"
                            class="text-gray-400 dark:text-gray-500"
                        >
                            @lang('admin::app.catalog.imports.log.empty')
                        </p>

                        <div
                            v-for="entry in logEntries"
                            :key="entry.id"
                            class="py-0.5"
                            :class="{
                                'text-green-700 dark:text-green-400': entry.action === 'created',
                                'text-blue-700 dark:text-blue-400': entry.action === 'updated',
                                'text-gray-500 dark:text-gray-400': entry.action === 'found',
                                'text-red-600 dark:text-red-400': entry.level === 'error',
                            }"
                        >
                            @{{ formatLogEntry(entry) }}
                        </div>
                    </div>
                </div>

                <!-- Errors Panel -->
                <div
                    v-if="errors.length > 0"
                    class="box-shadow rounded-sm border border-red-200 bg-red-50 p-4 dark:border-gray-700 dark:bg-gray-900"
                >
                    <h3 class="mb-2 text-sm font-semibold text-red-800 dark:text-red-400">
                        @lang('admin::app.catalog.imports.log.errors-title')
                    </h3>

                    <ul class="max-h-40 overflow-y-auto space-y-1 font-mono text-xs text-red-700 dark:text-red-400">
                        <li
                            v-for="(err, i) in errors"
                            :key="i"
                        >@{{ err }}</li>
                    </ul>
                </div>
```

- [ ] **Step 6: Build assets and manually verify**

```bash
npm run build
php artisan serve
```

Open `/admin/catalog/imports/create`, upload a CSV with a `categories` and/or `vendor` column, proceed to the show page, map columns, start import. Verify:
- Log panel appears with category/supplier entries immediately after starting
- Log panel shows product `created`/`updated` entries as batches complete (green / blue)
- Errors panel appears if there are validation errors in `$dtImport->errors`

- [ ] **Step 7: Commit**

```bash
git add packages/Webkul/ImportExport/src/Resources/views/admin/catalog/imports/show.blade.php \
        packages/Webkul/Admin/src/Resources/lang/en/app.php
git commit -m "feat(import-log): add real-time log panel and errors panel to import show page"
```

---

## Task 9: Final Test Run and Format

- [ ] **Step 1: Format changed PHP files**

```bash
vendor/bin/pint packages/Webkul/ImportExport/src/Http/Controllers/Admin/Catalog/ImportController.php \
                packages/Webkul/ImportExport/src/Models/CatalogImportLogEntry.php \
                packages/Webkul/ImportExport/src/Listeners/ProductsBatchSavedListener.php \
                packages/Webkul/ImportExport/src/Providers/ImportExportServiceProvider.php \
                packages/Webkul/DataTransfer/src/Helpers/Importers/Product/Importer.php
```

- [ ] **Step 2: Run the full ImportExport test suite**

```bash
php artisan test --compact packages/Webkul/ImportExport/tests/Feature/Catalog/ImportTest.php
```

Expected: all pass.

- [ ] **Step 3: Run the broader Admin suite for regressions**

```bash
php artisan test --compact --testsuite="Admin Feature Test"
```

Expected: all pass.

- [ ] **Step 4: Commit formatting**

```bash
git add -u
git commit -m "style: run pint on import-log changes"
```
