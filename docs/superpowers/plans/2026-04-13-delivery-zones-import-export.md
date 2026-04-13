# Delivery Zones GeoJSON Import / Export Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add GeoJSON bulk import and export to the delivery zones admin panel, plus make `city_id` optional on zones.

**Architecture:** Single-pass import: file upload + default city + inventory source + default rate → transaction creates all zones. Export: stream JSON download. DB migration makes `city_id` nullable and switches unique constraint from `(city_id, code)` composite to global `code`. All logic stays in `DeliveryZoneController`; no new service classes needed.

**Tech Stack:** Laravel 11, Pest 3, Bagisto admin Blade components, PostgreSQL

---

## File Map

| Action | File |
|---|---|
| Create | `packages/Webkul/Shipping/src/Database/Migrations/2026_04_13_000001_make_city_id_nullable_on_delivery_zones.php` |
| Modify | `packages/Webkul/DeliveryZones/src/Http/Requests/DeliveryZoneRequest.php` |
| Modify | `packages/Webkul/Admin/src/Routes/settings-routes.php` |
| Modify | `packages/Webkul/DeliveryZones/src/Http/Controllers/Admin/DeliveryZoneController.php` |
| Create | `packages/Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/import.blade.php` |
| Modify | `packages/Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/index.blade.php` |
| Modify | `packages/Webkul/Admin/src/Resources/lang/en/app.php` |
| Modify | `packages/Webkul/Admin/src/Resources/lang/ru/app.php` |
| Modify | `packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php` |

---

## Task 1: Migration — nullable city_id and global unique code

**Files:**
- Create: `packages/Webkul/Shipping/src/Database/Migrations/2026_04_13_000001_make_city_id_nullable_on_delivery_zones.php`
- Modify: `packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php`

- [ ] **Step 1: Write the failing test**

Append to `packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php`:

```php
it('should allow creating a delivery zone without a city', function () {
    $zone = \Webkul\Shipping\Models\DeliveryZone::query()->create([
        'city_id'                => null,
        'code'                   => 'cityless-zone-test',
        'name'                   => 'Cityless Zone',
        'polygon_json'           => [],
        'polygon_color'          => '#0077cc',
        'polygon_fill_opacity'   => 0.2,
        'polygon_stroke_opacity' => 1.0,
        'is_active'              => true,
    ]);

    expect($zone->city_id)->toBeNull()
        ->and($zone->code)->toBe('cityless-zone-test');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="should allow creating a delivery zone without a city"
```

Expected: FAIL — `city_id` violates NOT NULL constraint.

- [ ] **Step 3: Create the migration**

Create `packages/Webkul/Shipping/src/Database/Migrations/2026_04_13_000001_make_city_id_nullable_on_delivery_zones.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropUnique(['city_id', 'code']);
            $table->unsignedInteger('city_id')->nullable()->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_zones', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unsignedInteger('city_id')->nullable(false)->change();
            $table->unique(['city_id', 'code']);
        });
    }
};
```

- [ ] **Step 4: Run the migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_04_13_000001_make_city_id_nullable_on_delivery_zones` then `Migrated`.

- [ ] **Step 5: Run test to verify it passes**

```bash
php artisan test --compact --filter="should allow creating a delivery zone without a city"
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/Shipping/src/Database/Migrations/2026_04_13_000001_make_city_id_nullable_on_delivery_zones.php
git add packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php
git commit -m "feat(delivery-zones): make city_id nullable, switch unique to code-only"
```

---

## Task 2: Relax DeliveryZoneRequest validation

**Files:**
- Modify: `packages/Webkul/DeliveryZones/src/Http/Requests/DeliveryZoneRequest.php`

- [ ] **Step 1: Update the rules**

In `DeliveryZoneRequest::rules()`, change these two lines:

```php
// Before:
'city_id' => ['required', 'exists:delivery_cities,id'],
'code' => ['required', 'alpha_dash', 'max:255', 'unique:delivery_zones,code,'.$this->id.',id,city_id,'.$this->city_id],

// After:
'city_id' => ['nullable', 'integer', 'exists:delivery_cities,id'],
'code' => ['required', 'alpha_dash', 'max:255', 'unique:delivery_zones,code,'.$this->id],
```

- [ ] **Step 2: Run the existing zone creation test to confirm nothing broke**

```bash
php artisan test --compact --filter="should create delivery city and delivery zone"
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/DeliveryZones/src/Http/Requests/DeliveryZoneRequest.php
git commit -m "feat(delivery-zones): allow zone creation without city, global code uniqueness"
```

---

## Task 3: Add translation keys

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/lang/en/app.php`
- Modify: `packages/Webkul/Admin/src/Resources/lang/ru/app.php`

- [ ] **Step 1: Add keys to `en/app.php`**

Inside the `'delivery_zones'` array, add a `'zones-import'` key after `'zones-create'`, and extend `'zones-index'` and `'response'`:

In the `'zones-index'` block add two keys:
```php
'zones-index' => [
    'title'        => 'Delivery Zones',
    'heading'      => 'Delivery Zones',
    'add-zone'     => 'Add Zone',
    'import-zones' => 'Import',   // ADD
    'export-zones' => 'Export',   // ADD
],
```

After `'zones-create'`, add:
```php
'zones-import' => [
    'title'                    => 'Import Delivery Zones',
    'heading'                  => 'Import Delivery Zones',
    'import-btn'               => 'Import',
    'back-btn'                 => 'Back',
    'file'                     => 'GeoJSON File',
    'default-city'             => 'Default City (fallback)',
    'select-city'              => 'Select city (optional)',
    'inventory-source'         => 'Inventory Source',
    'select-inventory-source'  => 'Select inventory source',
    'default-rate'             => 'Default Rate',
    'min-order-total'          => 'Min Order Total',
    'price'                    => 'Price',
],
```

In the `'response'` block add:
```php
'zones-imported'           => ':count zone(s) imported successfully.',
'import-invalid-json'      => 'The file is not valid JSON.',
'import-invalid-geojson'   => 'The file must be a GeoJSON FeatureCollection.',
'import-no-features'       => 'The file contains no features.',
'import-failed'            => 'Import failed: :error',
```

- [ ] **Step 2: Add keys to `ru/app.php`**

Same positions as above, with Russian translations:

In `'zones-index'`:
```php
'import-zones' => 'Импорт',
'export-zones' => 'Экспорт',
```

New `'zones-import'` block:
```php
'zones-import' => [
    'title'                    => 'Импорт зон доставки',
    'heading'                  => 'Импорт зон доставки',
    'import-btn'               => 'Импортировать',
    'back-btn'                 => 'Назад',
    'file'                     => 'GeoJSON файл',
    'default-city'             => 'Город по умолчанию (запасной)',
    'select-city'              => 'Выберите город (необязательно)',
    'inventory-source'         => 'Источник инвентаря',
    'select-inventory-source'  => 'Выберите источник инвентаря',
    'default-rate'             => 'Тариф по умолчанию',
    'min-order-total'          => 'Мин. сумма заказа',
    'price'                    => 'Цена',
],
```

In `'response'`:
```php
'zones-imported'           => 'Импортировано зон: :count.',
'import-invalid-json'      => 'Файл содержит некорректный JSON.',
'import-invalid-geojson'   => 'Файл должен быть GeoJSON FeatureCollection.',
'import-no-features'       => 'Файл не содержит объектов.',
'import-failed'            => 'Ошибка импорта: :error',
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/lang/en/app.php
git add packages/Webkul/Admin/src/Resources/lang/ru/app.php
git commit -m "feat(delivery-zones): add import/export translation keys"
```

---

## Task 4: Register routes

**Files:**
- Modify: `packages/Webkul/Admin/src/Routes/settings-routes.php`

- [ ] **Step 1: Add three routes inside the `delivery-zones` group**

The current `delivery-zones` group (lines 121–133) ends after `Route::delete`. Add three routes before the closing `});`:

```php
Route::controller(DeliveryZoneController::class)->prefix('delivery-zones')->group(function () {
    Route::get('', 'index')->name('admin.settings.delivery_zones.index');

    Route::get('create', 'create')->name('admin.settings.delivery_zones.create');

    Route::post('create', 'store')->name('admin.settings.delivery_zones.store');

    Route::get('edit/{id}', 'edit')->name('admin.settings.delivery_zones.edit');

    Route::put('edit/{id}', 'update')->name('admin.settings.delivery_zones.update');

    Route::delete('edit/{id}', 'destroy')->name('admin.settings.delivery_zones.delete');

    // --- NEW ---
    Route::get('import', 'importForm')->name('admin.settings.delivery_zones.import');

    Route::post('import', 'import')->name('admin.settings.delivery_zones.import.store');

    Route::get('export', 'export')->name('admin.settings.delivery_zones.export');
});
```

- [ ] **Step 2: Verify routes are registered**

```bash
php artisan route:list --name=delivery_zones
```

Expected output includes:
```
GET|HEAD  settings/delivery-zones/import   admin.settings.delivery_zones.import
POST      settings/delivery-zones/import   admin.settings.delivery_zones.import.store
GET|HEAD  settings/delivery-zones/export   admin.settings.delivery_zones.export
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Admin/src/Routes/settings-routes.php
git commit -m "feat(delivery-zones): register import and export routes"
```

---

## Task 5: Implement export

**Files:**
- Modify: `packages/Webkul/DeliveryZones/src/Http/Controllers/Admin/DeliveryZoneController.php`
- Modify: `packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php`

- [ ] **Step 1: Write the failing test**

Append to `DeliveryZonesTest.php`:

```php
it('should export delivery zones as GeoJSON download', function () {
    $this->loginAsAdmin();

    $city = \Webkul\Shipping\Models\DeliveryCity::query()->create([
        'code'      => 'export-test-city',
        'name'      => 'Export Test City',
        'country'   => 'RU',
        'state'     => 'MSK',
        'is_active' => true,
    ]);

    \Webkul\Shipping\Models\DeliveryZone::query()->create([
        'city_id'                => $city->id,
        'code'                   => 'export-test-city',
        'name'                   => 'export-test-city',
        'polygon_json'           => [[82.93, 55.24], [82.94, 55.22], [82.96, 55.23]],
        'polygon_color'          => '#b51eff',
        'polygon_fill_opacity'   => 0.1,
        'polygon_stroke_opacity' => 0.1,
        'is_active'              => true,
    ]);

    $response = get(route('admin.settings.delivery_zones.export'));

    $response->assertOk();
    $response->assertHeader('Content-Disposition');

    $data = json_decode($response->getContent(), true);

    expect($data['type'])->toBe('FeatureCollection')
        ->and($data['features'])->not->toBeEmpty();

    $feature = collect($data['features'])
        ->firstWhere('properties.description', '#cid=export-test-city');

    expect($feature)->not->toBeNull()
        ->and($feature['properties']['fill'])->toBe('#b51eff')
        ->and($feature['properties']['fill-opacity'])->toBe(0.1)
        ->and($feature['properties']['stroke-opacity'])->toBe(0.1)
        ->and($feature['geometry']['type'])->toBe('Polygon');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="should export delivery zones as GeoJSON download"
```

Expected: FAIL — method `export` does not exist.

- [ ] **Step 3: Add `export()` to the controller**

Add the following `use` statement at the top of `DeliveryZoneController.php` (alongside existing uses):

```php
use Symfony\Component\HttpFoundation\StreamedResponse;
```

Add the method inside `DeliveryZoneController`:

```php
public function export(): StreamedResponse
{
    $zones = DeliveryZone::query()->with('city')->get();

    $features = $zones->values()->map(function (DeliveryZone $zone, int $index): array {
        return [
            'type'     => 'Feature',
            'id'       => $index,
            'geometry' => [
                'type'        => 'Polygon',
                'coordinates' => [$zone->polygon_json],
            ],
            'properties' => [
                'description'    => $zone->city ? '#cid='.$zone->city->code : '',
                'fill'           => $zone->polygon_color,
                'fill-opacity'   => $zone->polygon_fill_opacity,
                'stroke'         => $zone->polygon_color,
                'stroke-width'   => '1',
                'stroke-opacity' => $zone->polygon_stroke_opacity,
            ],
        ];
    })->all();

    $geojson = [
        'type'     => 'FeatureCollection',
        'metadata' => [
            'name'    => 'Delivery Zones',
            'creator' => 'Admin App Zone Editor',
        ],
        'features' => $features,
    ];

    return response()->streamDownload(
        function () use ($geojson): void {
            echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        },
        'delivery-zones.json',
        ['Content-Type' => 'application/json']
    );
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --compact --filter="should export delivery zones as GeoJSON download"
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/DeliveryZones/src/Http/Controllers/Admin/DeliveryZoneController.php
git add packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php
git commit -m "feat(delivery-zones): add GeoJSON export endpoint"
```

---

## Task 6: Import view

**Files:**
- Create: `packages/Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/import.blade.php`

- [ ] **Step 1: Create the view**

```blade
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.delivery_zones.zones-import.title')
    </x-slot>

    <form
        action="{{ route('admin.settings.delivery_zones.import.store') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.settings.delivery_zones.zones-import.heading')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.settings.delivery_zones.index') }}" class="transparent-button">
                    @lang('admin::app.settings.delivery_zones.zones-import.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('admin::app.settings.delivery_zones.zones-import.import-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 box-shadow rounded bg-white p-4 dark:bg-gray-900">
            @if ($errors->any())
                <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900 dark:text-red-200">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- File --}}
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                    @lang('admin::app.settings.delivery_zones.zones-import.file')
                </label>
                <input
                    type="file"
                    name="file"
                    accept=".json,application/json"
                    class="control w-full"
                    required
                />
            </div>

            {{-- Default city --}}
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white">
                    @lang('admin::app.settings.delivery_zones.zones-import.default-city')
                </label>
                <select name="default_city_id" class="control w-full">
                    <option value="">@lang('admin::app.settings.delivery_zones.zones-import.select-city')</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city->id }}" @selected(old('default_city_id') == $city->id)>
                            {{ $city->name }} ({{ $city->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Inventory source --}}
            <div class="mb-4">
                <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                    @lang('admin::app.settings.delivery_zones.zones-import.inventory-source')
                </label>
                <select name="inventory_source_id" class="control w-full" required>
                    <option value="">@lang('admin::app.settings.delivery_zones.zones-import.select-inventory-source')</option>
                    @foreach ($inventorySources as $source)
                        <option value="{{ $source->id }}" @selected(old('inventory_source_id') == $source->id)>
                            {{ $source->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Default rate --}}
            <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.settings.delivery_zones.zones-import.default-rate')
            </p>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                        @lang('admin::app.settings.delivery_zones.zones-import.min-order-total')
                    </label>
                    <input
                        type="number"
                        name="default_rate[min_order_total]"
                        step="0.01"
                        min="0"
                        value="{{ old('default_rate.min_order_total', 0) }}"
                        class="control w-full"
                        required
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white required">
                        @lang('admin::app.settings.delivery_zones.zones-import.price')
                    </label>
                    <input
                        type="number"
                        name="default_rate[price]"
                        step="0.01"
                        min="0"
                        value="{{ old('default_rate.price') }}"
                        class="control w-full"
                        required
                    />
                </div>
            </div>
        </div>
    </form>
</x-admin::layouts>
```

- [ ] **Step 2: Commit**

```bash
git add packages/Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/import.blade.php
git commit -m "feat(delivery-zones): add import form view"
```

---

## Task 7: Implement import controller methods and test

**Files:**
- Modify: `packages/Webkul/DeliveryZones/src/Http/Controllers/Admin/DeliveryZoneController.php`
- Modify: `packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php`

- [ ] **Step 1: Write failing tests**

Append to `DeliveryZonesTest.php`:

```php
it('should render the import delivery zones page', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.delivery_zones.import'))
        ->assertOk()
        ->assertSeeText('Import');
});

it('should import delivery zones from a GeoJSON file', function () {
    $this->loginAsAdmin();

    $city = \Webkul\Shipping\Models\DeliveryCity::query()->create([
        'code'      => 'novosibirsksuharnayaz4',
        'name'      => 'Novosibirsk Test',
        'country'   => 'RU',
        'state'     => 'NSK',
        'is_active' => true,
    ]);

    $inventorySource = InventorySource::factory()->create();

    $geojson = [
        'type'     => 'FeatureCollection',
        'metadata' => ['name' => 'Delivery Zones', 'creator' => 'Admin App Zone Editor'],
        'features' => [
            [
                'type' => 'Feature',
                'id'   => 0,
                'geometry' => [
                    'type'        => 'Polygon',
                    'coordinates' => [
                        [[82.93, 55.24], [82.94, 55.22], [82.96, 55.23], [82.93, 55.24]],
                    ],
                ],
                'properties' => [
                    'description'    => '#cid=novosibirsksuharnayaz4',
                    'fill'           => '#b51eff',
                    'fill-opacity'   => 0.1,
                    'stroke'         => '#b51eff',
                    'stroke-width'   => '1',
                    'stroke-opacity' => 0.1,
                ],
            ],
        ],
    ];

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        'zones.json',
        json_encode($geojson)
    );

    post(route('admin.settings.delivery_zones.import.store'), [
        'file'                => $file,
        'inventory_source_id' => $inventorySource->id,
        'default_rate'        => ['min_order_total' => 0, 'price' => 300],
    ])->assertRedirect(route('admin.settings.delivery_zones.index'));

    $zone = \Webkul\Shipping\Models\DeliveryZone::query()
        ->where('code', 'novosibirsksuharnayaz4')
        ->firstOrFail();

    expect($zone->city_id)->toBe($city->id)
        ->and($zone->polygon_color)->toBe('#b51eff')
        ->and($zone->polygon_fill_opacity)->toBe(0.1)
        ->and($zone->polygon_stroke_opacity)->toBe(0.1)
        ->and($zone->rates()->count())->toBe(1)
        ->and((float) $zone->rates()->first()->price)->toBe(300.0)
        ->and($zone->inventory_sources()->count())->toBe(1);
});

it('should fall back to default city when zone city code is not found', function () {
    $this->loginAsAdmin();

    $defaultCity = \Webkul\Shipping\Models\DeliveryCity::query()->create([
        'code'      => 'default-fallback-city',
        'name'      => 'Fallback City',
        'country'   => 'RU',
        'state'     => 'NSK',
        'is_active' => true,
    ]);

    $inventorySource = InventorySource::factory()->create();

    $geojson = [
        'type'     => 'FeatureCollection',
        'features' => [
            [
                'type' => 'Feature',
                'id'   => 0,
                'geometry' => [
                    'type'        => 'Polygon',
                    'coordinates' => [
                        [[82.93, 55.24], [82.94, 55.22], [82.96, 55.23], [82.93, 55.24]],
                    ],
                ],
                'properties' => [
                    'description'    => '#cid=unknown-city-xyz',
                    'fill'           => '#ff0000',
                    'fill-opacity'   => 0.2,
                    'stroke'         => '#ff0000',
                    'stroke-width'   => '1',
                    'stroke-opacity' => 0.8,
                ],
            ],
        ],
    ];

    $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
        'zones.json',
        json_encode($geojson)
    );

    post(route('admin.settings.delivery_zones.import.store'), [
        'file'            => $file,
        'inventory_source_id' => $inventorySource->id,
        'default_city_id' => $defaultCity->id,
        'default_rate'    => ['min_order_total' => 0, 'price' => 150],
    ])->assertRedirect(route('admin.settings.delivery_zones.index'));

    $zone = \Webkul\Shipping\Models\DeliveryZone::query()
        ->where('code', 'unknown-city-xyz')
        ->firstOrFail();

    expect($zone->city_id)->toBe($defaultCity->id);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter="should render the import|should import delivery zones|should fall back to default city"
```

Expected: FAIL — methods `importForm` and `import` do not exist.

- [ ] **Step 3: Add `use` statements to the controller**

At the top of `DeliveryZoneController.php`, add:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
```

(Keep all existing `use` statements.)

- [ ] **Step 4: Add `importForm()` method to the controller**

```php
public function importForm()
{
    return view('delivery-zones::settings.delivery-zones.import', [
        'cities'           => DeliveryCity::query()->where('is_active', true)->orderBy('name')->get(),
        'inventorySources' => InventorySource::query()->where('status', true)->orderBy('name')->get(),
    ]);
}
```

- [ ] **Step 5: Add `import()` method to the controller**

```php
public function import(Request $request): RedirectResponse
{
    $request->validate([
        'file'                        => ['required', 'file'],
        'inventory_source_id'         => ['required', 'integer', 'exists:inventory_sources,id'],
        'default_city_id'             => ['nullable', 'integer', 'exists:delivery_cities,id'],
        'default_rate'                => ['required', 'array'],
        'default_rate.min_order_total' => ['required', 'numeric', 'min:0'],
        'default_rate.price'          => ['required', 'numeric', 'min:0'],
    ]);

    $contents = file_get_contents($request->file('file')->getRealPath());
    $data = json_decode($contents, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return back()->withErrors(['file' => trans('admin::app.settings.delivery_zones.response.import-invalid-json')]);
    }

    if (($data['type'] ?? '') !== 'FeatureCollection') {
        return back()->withErrors(['file' => trans('admin::app.settings.delivery_zones.response.import-invalid-geojson')]);
    }

    $features = $data['features'] ?? [];

    if (empty($features)) {
        return back()->withErrors(['file' => trans('admin::app.settings.delivery_zones.response.import-no-features')]);
    }

    $inventorySourceId = (int) $request->input('inventory_source_id');
    $defaultCityId     = $request->filled('default_city_id') ? (int) $request->input('default_city_id') : null;
    $defaultRate       = $request->input('default_rate');

    $citiesByCode = DeliveryCity::query()->pluck('id', 'code');

    try {
        DB::transaction(function () use ($features, $citiesByCode, $defaultCityId, $inventorySourceId, $defaultRate): void {
            foreach ($features as $feature) {
                $description = $feature['properties']['description'] ?? '';
                $code        = str_starts_with($description, '#cid=') ? substr($description, 5) : null;

                $cityId = ($code !== null && $citiesByCode->has($code))
                    ? $citiesByCode->get($code)
                    : $defaultCityId;

                $properties  = $feature['properties'] ?? [];
                $coordinates = $feature['geometry']['coordinates'][0] ?? [];

                $zone = DeliveryZone::query()->create([
                    'city_id'                => $cityId,
                    'code'                   => $code ?? 'zone_'.uniqid(),
                    'name'                   => $code ?? 'Imported Zone',
                    'polygon_json'           => $coordinates,
                    'polygon_color'          => $properties['fill'] ?? '#0077cc',
                    'polygon_fill_opacity'   => (float) ($properties['fill-opacity'] ?? 0.2),
                    'polygon_stroke_opacity' => (float) ($properties['stroke-opacity'] ?? 1.0),
                    'is_active'              => true,
                ]);

                $zone->inventory_sources()->sync([$inventorySourceId]);

                $zone->rates()->create([
                    'min_order_total' => $defaultRate['min_order_total'],
                    'price'           => $defaultRate['price'],
                    'sort_order'      => 0,
                ]);
            }
        });
    } catch (\Throwable $e) {
        return back()->withErrors([
            'file' => trans('admin::app.settings.delivery_zones.response.import-failed', ['error' => $e->getMessage()]),
        ]);
    }

    session()->flash('success', trans('admin::app.settings.delivery_zones.response.zones-imported', ['count' => count($features)]));

    return redirect()->route('admin.settings.delivery_zones.index');
}
```

- [ ] **Step 6: Run tests to verify they pass**

```bash
php artisan test --compact --filter="should render the import|should import delivery zones|should fall back to default city"
```

Expected: all 3 PASS.

- [ ] **Step 7: Run full delivery zones test suite**

```bash
php artisan test --compact --testsuite="Admin Feature Test" --filter="DeliveryZone"
```

Expected: all PASS.

- [ ] **Step 8: Commit**

```bash
git add packages/Webkul/DeliveryZones/src/Http/Controllers/Admin/DeliveryZoneController.php
git add packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php
git commit -m "feat(delivery-zones): add GeoJSON import controller methods and tests"
```

---

## Task 8: Update zones index view — add Import and Export buttons

**Files:**
- Modify: `packages/Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/index.blade.php`

- [ ] **Step 1: Replace the header section**

The current header:

```blade
<div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
    <p class="text-xl font-bold text-gray-800 dark:text-white">
        @lang('admin::app.settings.delivery_zones.zones-index.heading')
    </p>

    <a href="{{ route('admin.settings.delivery_zones.create') }}">
        <div class="primary-button">
            @lang('admin::app.settings.delivery_zones.zones-index.add-zone')
        </div>
    </a>
</div>
```

Replace with:

```blade
<div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
    <p class="text-xl font-bold text-gray-800 dark:text-white">
        @lang('admin::app.settings.delivery_zones.zones-index.heading')
    </p>

    <div class="flex items-center gap-x-2.5">
        <a href="{{ route('admin.settings.delivery_zones.export') }}" download="delivery-zones.json">
            <div class="transparent-button">
                @lang('admin::app.settings.delivery_zones.zones-index.export-zones')
            </div>
        </a>

        <a href="{{ route('admin.settings.delivery_zones.import') }}">
            <div class="transparent-button">
                @lang('admin::app.settings.delivery_zones.zones-index.import-zones')
            </div>
        </a>

        <a href="{{ route('admin.settings.delivery_zones.create') }}">
            <div class="primary-button">
                @lang('admin::app.settings.delivery_zones.zones-index.add-zone')
            </div>
        </a>
    </div>
</div>
```

- [ ] **Step 2: Run the index page test to confirm the page still renders**

```bash
php artisan test --compact --filter="should render delivery city and zone index pages"
```

Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/index.blade.php
git commit -m "feat(delivery-zones): add Import and Export buttons to zones index"
```

---

## Task 9: Final verification

- [ ] **Step 1: Run the full Admin Feature Test suite**

```bash
php artisan test --compact --testsuite="Admin Feature Test"
```

Expected: all tests PASS (no regressions).

- [ ] **Step 2: Format changed PHP files**

```bash
vendor/bin/pint packages/Webkul/DeliveryZones/src/Http/Controllers/Admin/DeliveryZoneController.php
vendor/bin/pint packages/Webkul/DeliveryZones/src/Http/Requests/DeliveryZoneRequest.php
vendor/bin/pint packages/Webkul/Shipping/src/Database/Migrations/2026_04_13_000001_make_city_id_nullable_on_delivery_zones.php
```

- [ ] **Step 3: Commit formatting fixes if any**

```bash
git add -p
git commit -m "style: pint formatting on delivery zones import/export files"
```

- [ ] **Step 4: Run tests once more after formatting**

```bash
php artisan test --compact --testsuite="Admin Feature Test"
```

Expected: all PASS.
