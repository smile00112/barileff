# Import Activity Log — Design Spec

**Date:** 2026-04-19  
**Branch:** `main`  
**Scope:** `packages/Webkul/ImportExport`, `packages/Webkul/DataTransfer`

---

## Goal

Add a scrollable, real-time activity log to the catalog import show page (`/admin/catalog/imports/{id}`). The log displays per-entity actions (product created/updated, category created, supplier created/found) as they happen during import, plus any runtime errors.

---

## Architecture

### New Table: `catalog_import_log_entries`

```
id               BIGINT UNSIGNED  PK AUTO_INCREMENT
session_id       BIGINT UNSIGNED  FK → catalog_import_sessions.id, CASCADE DELETE
level            VARCHAR(16)      'info' | 'warning' | 'error'
entity_type      VARCHAR(32)      'product' | 'category' | 'supplier' | 'import'
action           VARCHAR(32)      'created' | 'updated' | 'found' | 'error' | 'started' | 'completed'
entity_id        BIGINT UNSIGNED  NULL  (product.id, category.id, supplier.id)
message          TEXT             NULL  (error message, category name, supplier name)
created_at       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
```

No `updated_at`. Entries are immutable append-only. Index on `(session_id, id)` for efficient "entries after N" queries.

### New Model: `CatalogImportLogEntry`

Location: `packages/Webkul/ImportExport/src/Models/CatalogImportLogEntry.php`

- `$fillable`: all columns
- `$timestamps = false` (only `created_at`, set manually)
- Relation: `belongsTo(CatalogImportSession::class)`

---

## Where Log Entries Are Written

### Categories (synchronous, in `ImportController::start()`)

`createMissingCategories(CatalogImportSession $session)` currently returns `void`. Change it to return `array<int, array{id: int, name: string}>` — one entry for each category that was actually created (not already-existing ones). After the DataTransfer import starts, bulk-insert `catalog_import_log_entries` rows with `entity_type=category`, `action=created`.

### Suppliers (synchronous, in `ImportController::start()`)

The supplier pre-scan + creation currently lives inside `createRemappedCsv()`. Extract it into a new protected method:

```php
protected function resolveImportSuppliers(CatalogImportSession $session): array
```

Returns `['map' => [name => id], 'events' => [['action' => 'created'|'found', 'id' => int, 'name' => str], ...]]`.

`createRemappedCsv()` receives the pre-resolved `$supplierNameToId` map as a parameter (instead of building it internally). `start()` calls `resolveImportSuppliers()` first, then passes the map to `createRemappedCsv()`, then bulk-inserts supplier log entries.

### Products (asynchronous, from queue job)

Modify `DataTransfer/Helpers/Importers/Product/Importer::saveProducts()`:

After inserting new products (the `$newProducts` query already runs to populate SKU storage), collect created IDs. For updated products, collect IDs from `$this->skuStorage->get($sku)['id']` before the upsert.

Fire a generic string event:

```php
Event::dispatch('catalog_import.products_saved', [
    'import_id'   => $this->import->id,
    'created_ids' => [...],   // int[]
    'updated_ids' => [...],   // int[]
]);
```

No package dependency: `DataTransfer` fires a plain string event; `ImportExport` subscribes to it.

### Event Listener in ImportExport

`packages/Webkul/ImportExport/src/Listeners/ProductsBatchSavedListener.php`

On `catalog_import.products_saved`:
1. Look up `CatalogImportSession` by `import_ref_id = $payload['import_id']` (one query).
2. If not found, return (unrelated import, e.g. from standard DataTransfer UI).
3. Bulk-insert log entries for created and updated product IDs.

Registered in `ImportExport/Providers/ImportExportServiceProvider` via `Event::listen()`.

---

## API: `status()` Endpoint Changes

### Request

```
GET /admin/catalog/imports/{id}/status?after_log_id=0
```

`after_log_id` (integer, default 0) — return only entries with `id > after_log_id`. The frontend tracks the last seen ID and passes it on each poll to get only new entries (streaming without re-fetching everything).

### Response (additions)

```json
{
  "state": "processing",
  "stats": { ... },
  "import_state": "...",
  "log_entries": [
    {
      "id": 42,
      "level": "info",
      "entity_type": "category",
      "action": "created",
      "entity_id": 15,
      "message": "Electronics",
      "created_at": "2026-04-19T10:12:03Z"
    },
    ...
  ],
  "errors": [
    "Row 7: Product type is invalid or not supported",
    ...
  ]
}
```

`errors` comes from `$dtImport->errors` (already stored in the Import model by the DataTransfer importer). Returned on all polls (not incremental), since the list only grows at validation time.

---

## Show Page Changes (`show.blade.php`)

### Log Panel

Visible when `state` is `processing`, `completed`, or `failed`.

- Fixed-height (`max-h-96`) scrollable `<div>` with `overflow-y-auto`
- Each entry is one line: `[timestamp] [icon] entity_type #entity_id — action` or message for errors
- Color coding:
  - `created` → green text
  - `updated` → blue text
  - `found` (supplier already existed) → gray text
  - `error` → red text
- Auto-scrolls to bottom when new entries arrive (unless user has scrolled up)

### Polling Changes

Vue data adds:
```js
logEntries: [],
lastLogId: 0,
```

On each `pollStatus()` call, append `?after_log_id=this.lastLogId` to the status URL. On response, push new `log_entries` into `logEntries`, update `lastLogId = last entry's id`.

### Errors Panel

Below the log panel (or inside the completed/failed blocks), show `errors[]` from the status response as a red-bordered list. Hidden when empty.

---

## Lang Keys to Add

In `packages/Webkul/Admin/src/Resources/lang/en/app.php` under `catalog.imports`:

```php
'log' => [
    'title'            => 'Activity Log',
    'empty'            => 'No activity yet.',
    'entity-product'   => 'Product',
    'entity-category'  => 'Category',
    'entity-supplier'  => 'Supplier',
    'entity-import'    => 'Import',
    'action-created'   => 'created',
    'action-updated'   => 'updated',
    'action-found'     => 'found (existing)',
    'action-error'     => 'error',
    'errors-title'     => 'Import Errors',
],
```

---

## Files Changed

| File | Change |
|---|---|
| `ImportExport/.../Database/Migrations/...create_catalog_import_log_entries_table.php` | New migration |
| `ImportExport/.../Models/CatalogImportLogEntry.php` | New model |
| `ImportExport/.../Listeners/ProductsBatchSavedListener.php` | New listener |
| `ImportExport/.../Providers/ImportExportServiceProvider.php` | Register listener |
| `ImportExport/.../Http/Controllers/Admin/Catalog/ImportController.php` | Extract `resolveImportSuppliers()`, modify `createMissingCategories()`, write log entries in `start()`, return log in `status()` |
| `DataTransfer/.../Helpers/Importers/Product/Importer.php` | Fire `catalog_import.products_saved` event in `saveProducts()` |
| `ImportExport/.../Resources/views/admin/catalog/imports/show.blade.php` | Add log panel + errors panel |
| `Admin/.../Resources/lang/en/app.php` | Add `catalog.imports.log.*` keys |

---

## Constraints

- No new Composer dependencies.
- `DataTransfer` package fires a plain string event only — no import of `ImportExport` classes.
- Log entries are soft-deleted via `CASCADE` when the session is deleted (FK constraint).
- For very large imports (10k+ products), bulk-insert log entries in one `INSERT` per batch (not one query per product).
