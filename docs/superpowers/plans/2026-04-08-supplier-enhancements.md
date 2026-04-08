# Supplier Module Enhancements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add description, image upload, and sort_order fields to suppliers with enhanced DataGrid display and product integration

**Architecture:** Extend existing Supplier package with new fields via migration, add image upload handling to repository, update DataGrids for suppliers and products with JOIN-based product counts, integrate supplier dropdown into product forms

**Tech Stack:** Laravel 11, Pest 3, Bagisto DataGrid, Laravel Storage, PostgreSQL/MySQL

---

## File Structure

**New Files:**
- `packages/Webkul/Supplier/src/Database/Migrations/2026_04_08_000001_add_supplier_fields_to_suppliers_table.php` - Migration for new fields
- `packages/Webkul/Supplier/tests/Feature/SupplierEnhancementsTest.php` - Feature tests

**Modified Files:**
- `packages/Webkul/Supplier/src/Models/Supplier.php` - Add new fields to fillable
- `packages/Webkul/Supplier/src/Repositories/SupplierRepository.php` - Image upload/delete logic
- `packages/Webkul/Supplier/src/Http/Requests/SupplierRequest.php` - Validation for new fields
- `packages/Webkul/Supplier/src/DataGrids/SupplierDataGrid.php` - Products count, image column
- `packages/Webkul/Supplier/src/Http/Controllers/Admin/SupplierController.php` - Error handling
- `packages/Webkul/Supplier/src/Resources/views/admin/create.blade.php` - New form fields
- `packages/Webkul/Supplier/src/Resources/views/admin/edit.blade.php` - New form fields with image preview
- `packages/Webkul/Supplier/src/Resources/lang/en/app.php` - English translations
- `packages/Webkul/Supplier/src/Resources/lang/ru/app.php` - Russian translations
- `packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php` - Supplier filter
- `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php` - Supplier dropdown
- `packages/Webkul/Admin/src/Resources/lang/en/app.php` - Product translations
- `packages/Webkul/Admin/src/Resources/lang/ru/app.php` - Product translations

---

## Task 1: Database Migration for New Supplier Fields

**Files:**
- Create: `packages/Webkul/Supplier/src/Database/Migrations/2026_04_08_000001_add_supplier_fields_to_suppliers_table.php`

- [ ] **Step 1: Create migration file**

```bash
cd d:/_WORK_/laragon_2025/www/my_bagisto
touch packages/Webkul/Supplier/src/Database/Migrations/2026_04_08_000001_add_supplier_fields_to_suppliers_table.php
```

- [ ] **Step 2: Write migration content**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('image')->nullable()->after('description');
            $table->integer('sort_order')->default(0)->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['description', 'image', 'sort_order']);
        });
    }
};
```

- [ ] **Step 3: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully, new columns added to suppliers table

- [ ] **Step 4: Verify schema**

Run: `php artisan db:show --table=suppliers`
Expected: Columns `description`, `image`, `sort_order` present in schema

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Supplier/src/Database/Migrations/2026_04_08_000001_add_supplier_fields_to_suppliers_table.php
git commit -m "feat(supplier): add description, image, sort_order fields to suppliers table"
```

---

## Task 2: Update Supplier Model

**Files:**
- Modify: `packages/Webkul/Supplier/src/Models/Supplier.php:15-23`

- [ ] **Step 1: Add new fields to fillable array**

```php
<?php

namespace Webkul\Supplier\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Product\Models\ProductProxy;
use Webkul\Supplier\Contracts\Supplier as SupplierContract;

class Supplier extends Model implements SupplierContract
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'sort_order',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProductProxy::modelClass(), 'supplier_id');
    }
}
```

- [ ] **Step 2: Run code formatter**

Run: `vendor/bin/pint packages/Webkul/Supplier/src/Models/Supplier.php`
Expected: Code formatted according to Laravel preset

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Supplier/src/Models/Supplier.php
git commit -m "feat(supplier): add description, image, sort_order to model fillable"
```

---

## Task 3: Update SupplierRequest Validation

**Files:**
- Modify: `packages/Webkul/Supplier/src/Http/Requests/SupplierRequest.php:14-25`

- [ ] **Step 1: Add validation rules for new fields**

```php
<?php

namespace Webkul\Supplier\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'boolean'],
        ];
    }
}
```

- [ ] **Step 2: Run code formatter**

Run: `vendor/bin/pint packages/Webkul/Supplier/src/Http/Requests/SupplierRequest.php`
Expected: Code formatted

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Supplier/src/Http/Requests/SupplierRequest.php
git commit -m "feat(supplier): add validation for description, image, sort_order fields"
```

---

## Task 4: Add Image Handling to SupplierRepository

**Files:**
- Modify: `packages/Webkul/Supplier/src/Repositories/SupplierRepository.php`

- [ ] **Step 1: Import Storage facade at top of file**

Add after namespace:
```php
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
```

- [ ] **Step 2: Add create method with image upload**

```php
<?php

namespace Webkul\Supplier\Repositories;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;

class SupplierRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\Supplier\Contracts\Supplier';
    }

    /**
     * Create supplier with image upload handling
     */
    public function create(array $data)
    {
        if (isset($data['image']) && $data['image']) {
            $data['image'] = $this->uploadImage($data['image']);
        }

        return parent::create($data);
    }

    /**
     * Update supplier with image replacement handling
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $supplier = $this->findOrFail($id);

        // Handle image removal checkbox
        if (isset($data['remove_image']) && $data['remove_image']) {
            $this->deleteImage($supplier->image);
            $data['image'] = null;
        }

        // Handle new image upload
        if (isset($data['image']) && $data['image']) {
            $this->deleteImage($supplier->image);
            $data['image'] = $this->uploadImage($data['image']);
        }

        return parent::update($data, $id, $attribute);
    }

    /**
     * Delete supplier with image cleanup and product check
     */
    public function delete($id)
    {
        $supplier = $this->findOrFail($id);

        // Prevent deletion if supplier has products
        $productsCount = $supplier->products()->count();
        if ($productsCount > 0) {
            throw new \Exception(
                trans('supplier::app.admin.delete-failed', ['count' => $productsCount])
            );
        }

        // Delete associated image
        $this->deleteImage($supplier->image);

        return parent::delete($id);
    }

    /**
     * Upload supplier image to storage
     */
    protected function uploadImage($image): string
    {
        try {
            return $image->store('supplier_images', 'public');
        } catch (\Exception $e) {
            Log::error('Supplier image upload failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(trans('supplier::app.admin.image-upload-failed'));
        }
    }

    /**
     * Delete supplier image from storage
     */
    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
```

- [ ] **Step 3: Run code formatter**

Run: `vendor/bin/pint packages/Webkul/Supplier/src/Repositories/SupplierRepository.php`
Expected: Code formatted

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Supplier/src/Repositories/SupplierRepository.php
git commit -m "feat(supplier): add image upload, deletion, and product check to repository"
```

---

## Task 5: Update SupplierController Error Handling

**Files:**
- Modify: `packages/Webkul/Supplier/src/Http/Controllers/Admin/SupplierController.php:31-62`

- [ ] **Step 1: Add try-catch blocks to controller methods**

```php
<?php

namespace Webkul\Supplier\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Supplier\DataGrids\SupplierDataGrid;
use Webkul\Supplier\Http\Requests\SupplierRequest;
use Webkul\Supplier\Repositories\SupplierRepository;

class SupplierController extends Controller
{
    public function __construct(protected SupplierRepository $supplierRepository) {}

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(SupplierDataGrid::class)->toJson();
        }

        return view('supplier::admin.index');
    }

    public function create(): View
    {
        return view('supplier::admin.create');
    }

    public function store(SupplierRequest $request): RedirectResponse
    {
        try {
            $this->supplierRepository->create($request->validated());

            session()->flash('success', trans('supplier::app.admin.created'));
        } catch (\Exception $e) {
            Log::error('Supplier creation failed', [
                'error' => $e->getMessage(),
                'data' => $request->safe()->only(['name', 'sort_order']),
            ]);

            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.suppliers.index');
    }

    public function edit(int $id): View
    {
        $supplier = $this->supplierRepository->findOrFail($id);

        return view('supplier::admin.edit', compact('supplier'));
    }

    public function update(SupplierRequest $request, int $id): RedirectResponse
    {
        try {
            $this->supplierRepository->update($request->validated(), $id);

            session()->flash('success', trans('supplier::app.admin.updated'));
        } catch (\Exception $e) {
            Log::error('Supplier update failed', [
                'supplier_id' => $id,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', $e->getMessage());
        }

        return redirect()->route('admin.suppliers.index');
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->supplierRepository->delete($id);

            return new JsonResponse([
                'message' => trans('supplier::app.admin.deleted'),
            ]);
        } catch (\Exception $e) {
            Log::error('Supplier deletion failed', [
                'supplier_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
```

- [ ] **Step 2: Run code formatter**

Run: `vendor/bin/pint packages/Webkul/Supplier/src/Http/Controllers/Admin/SupplierController.php`
Expected: Code formatted

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Supplier/src/Http/Controllers/Admin/SupplierController.php
git commit -m "feat(supplier): add error handling and logging to controller"
```

---

## Task 6: Update SupplierDataGrid with Products Count and Image

**Files:**
- Modify: `packages/Webkul/Supplier/src/DataGrids/SupplierDataGrid.php`

- [ ] **Step 1: Update query builder with JOIN and products count**

```php
<?php

namespace Webkul\Supplier\DataGrids;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataGrid\DataGrid;

class SupplierDataGrid extends DataGrid
{
    protected $primaryColumn = 'supplier_id';

    public function prepareQueryBuilder(): \Illuminate\Database\Query\Builder
    {
        $tablePrefix = DB::getTablePrefix();

        return DB::table('suppliers')
            ->leftJoin('products', 'suppliers.id', '=', 'products.supplier_id')
            ->select(
                'suppliers.id as supplier_id',
                'suppliers.name',
                'suppliers.image',
                'suppliers.description',
                'suppliers.sort_order',
                'suppliers.status',
            )
            ->addSelect(DB::raw('COUNT(DISTINCT '.$tablePrefix.'products.id) as products_count'))
            ->groupBy([
                'suppliers.id',
                'suppliers.name',
                'suppliers.image',
                'suppliers.description',
                'suppliers.sort_order',
                'suppliers.status',
            ])
            ->orderBy('suppliers.sort_order', 'asc')
            ->orderBy('suppliers.name', 'asc');
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index' => 'supplier_id',
            'label' => trans('supplier::app.admin.datagrid.id'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'image',
            'label' => trans('supplier::app.admin.datagrid.image'),
            'type' => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable' => false,
            'closure' => function ($row) {
                if ($row->image) {
                    $url = Storage::url($row->image);

                    return sprintf(
                        '<img src="%s" class="w-12 h-12 object-cover rounded" alt="%s">',
                        $url,
                        htmlspecialchars($row->name)
                    );
                }

                return '<div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                            <i class="icon-image text-gray-400"></i>
                        </div>';
            },
        ]);

        $this->addColumn([
            'index' => 'name',
            'label' => trans('supplier::app.admin.datagrid.name'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'products_count',
            'label' => trans('supplier::app.admin.datagrid.products-count'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable' => true,
            'closure' => function ($row) {
                if ($row->products_count > 0) {
                    $url = route('admin.catalog.products.index', [
                        'filters[supplier_id]' => $row->supplier_id,
                    ]);

                    return sprintf(
                        '<a href="%s" class="text-blue-600 hover:underline">%d</a>',
                        $url,
                        $row->products_count
                    );
                }

                return '<span class="text-gray-400">0</span>';
            },
        ]);

        $this->addColumn([
            'index' => 'sort_order',
            'label' => trans('supplier::app.admin.datagrid.sort-order'),
            'type' => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('supplier::app.admin.datagrid.status'),
            'type' => 'boolean',
            'searchable' => false,
            'filterable' => true,
            'sortable' => true,
            'closure' => fn ($row) => $row->status
                ? '<span class="badge badge-md badge-success">'.trans('supplier::app.admin.datagrid.active').'</span>'
                : '<span class="badge badge-md badge-danger">'.trans('supplier::app.admin.datagrid.inactive').'</span>',
        ]);
    }

    public function prepareActions(): void
    {
        $this->addAction([
            'icon' => 'icon-edit',
            'title' => trans('supplier::app.admin.datagrid.edit'),
            'method' => 'GET',
            'url' => fn ($row) => route('admin.suppliers.edit', $row->supplier_id),
        ]);

        $this->addAction([
            'icon' => 'icon-delete',
            'title' => trans('supplier::app.admin.datagrid.delete'),
            'method' => 'DELETE',
            'url' => fn ($row) => route('admin.suppliers.destroy', $row->supplier_id),
        ]);
    }
}
```

- [ ] **Step 2: Run code formatter**

Run: `vendor/bin/pint packages/Webkul/Supplier/src/DataGrids/SupplierDataGrid.php`
Expected: Code formatted

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Supplier/src/DataGrids/SupplierDataGrid.php
git commit -m "feat(supplier): add image, products count, sort order to DataGrid"
```

---

## Task 7: Add English Translations for Supplier Package

**Files:**
- Modify: `packages/Webkul/Supplier/src/Resources/lang/en/app.php`

- [ ] **Step 1: Read current translations**

Run: `cat packages/Webkul/Supplier/src/Resources/lang/en/app.php`
Expected: See existing translation structure

- [ ] **Step 2: Add new translation keys**

```php
<?php

return [
    'admin' => [
        'index' => [
            'title' => 'Suppliers',
            'create-btn' => 'Create Supplier',
        ],

        'create' => [
            'title' => 'Create Supplier',
            'save-btn' => 'Save Supplier',
            'name' => 'Name',
            'description' => 'Description',
            'image' => 'Image',
            'sort-order' => 'Sort Order',
            'contact-name' => 'Contact Name',
            'contact-email' => 'Contact Email',
            'contact-phone' => 'Contact Phone',
            'address' => 'Address',
            'notes' => 'Notes',
            'status' => 'Status',
        ],

        'edit' => [
            'title' => 'Edit Supplier',
            'save-btn' => 'Save Supplier',
            'name' => 'Name',
            'description' => 'Description',
            'image' => 'Image',
            'remove-image' => 'Remove current image',
            'sort-order' => 'Sort Order',
            'contact-name' => 'Contact Name',
            'contact-email' => 'Contact Email',
            'contact-phone' => 'Contact Phone',
            'address' => 'Address',
            'notes' => 'Notes',
            'status' => 'Status',
        ],

        'datagrid' => [
            'id' => 'ID',
            'name' => 'Name',
            'image' => 'Image',
            'products-count' => 'Products',
            'sort-order' => 'Sort Order',
            'contact-name' => 'Contact Name',
            'contact-email' => 'Contact Email',
            'status' => 'Status',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],

        'created' => 'Supplier created successfully.',
        'updated' => 'Supplier updated successfully.',
        'deleted' => 'Supplier deleted successfully.',
        'delete-failed' => 'Cannot delete supplier. It has :count products associated.',
        'create-failed' => 'Failed to create supplier. Please try again.',
        'update-failed' => 'Failed to update supplier. Please try again.',
        'image-upload-failed' => 'Failed to upload image. Please try again.',
    ],
];
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Supplier/src/Resources/lang/en/app.php
git commit -m "feat(supplier): add English translations for new fields"
```

---

## Task 8: Add Russian Translations for Supplier Package

**Files:**
- Modify: `packages/Webkul/Supplier/src/Resources/lang/ru/app.php`

- [ ] **Step 1: Add Russian translation keys**

```php
<?php

return [
    'admin' => [
        'index' => [
            'title' => 'Поставщики',
            'create-btn' => 'Создать поставщика',
        ],

        'create' => [
            'title' => 'Создать поставщика',
            'save-btn' => 'Сохранить поставщика',
            'name' => 'Название',
            'description' => 'Описание',
            'image' => 'Изображение',
            'sort-order' => 'Порядок сортировки',
            'contact-name' => 'Контактное лицо',
            'contact-email' => 'Email',
            'contact-phone' => 'Телефон',
            'address' => 'Адрес',
            'notes' => 'Примечания',
            'status' => 'Статус',
        ],

        'edit' => [
            'title' => 'Редактировать поставщика',
            'save-btn' => 'Сохранить поставщика',
            'name' => 'Название',
            'description' => 'Описание',
            'image' => 'Изображение',
            'remove-image' => 'Удалить текущее изображение',
            'sort-order' => 'Порядок сортировки',
            'contact-name' => 'Контактное лицо',
            'contact-email' => 'Email',
            'contact-phone' => 'Телефон',
            'address' => 'Адрес',
            'notes' => 'Примечания',
            'status' => 'Статус',
        ],

        'datagrid' => [
            'id' => 'ID',
            'name' => 'Название',
            'image' => 'Изображение',
            'products-count' => 'Товары',
            'sort-order' => 'Сортировка',
            'contact-name' => 'Контактное лицо',
            'contact-email' => 'Email',
            'status' => 'Статус',
            'active' => 'Активен',
            'inactive' => 'Неактивен',
            'edit' => 'Редактировать',
            'delete' => 'Удалить',
        ],

        'created' => 'Поставщик успешно создан.',
        'updated' => 'Поставщик успешно обновлен.',
        'deleted' => 'Поставщик успешно удален.',
        'delete-failed' => 'Невозможно удалить поставщика. С ним связано товаров: :count.',
        'create-failed' => 'Не удалось создать поставщика. Попробуйте снова.',
        'update-failed' => 'Не удалось обновить поставщика. Попробуйте снова.',
        'image-upload-failed' => 'Не удалось загрузить изображение. Попробуйте снова.',
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add packages/Webkul/Supplier/src/Resources/lang/ru/app.php
git commit -m "feat(supplier): add Russian translations for new fields"
```

---

## Task 9: Update Supplier Create Form

**Files:**
- Modify: `packages/Webkul/Supplier/src/Resources/views/admin/create.blade.php`

- [ ] **Step 1: Read existing create form**

Run: `cat packages/Webkul/Supplier/src/Resources/views/admin/create.blade.php`
Expected: See current form structure

- [ ] **Step 2: Add new form fields for description, image, sort_order**

Add after the name field and before contact fields. The complete form should look like:

```blade
<x-admin::layouts>
    <x-slot:title>
        @lang('supplier::app.admin.create.title')
    </x-slot>

    <form method="POST" action="{{ route('admin.suppliers.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('supplier::app.admin.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.suppliers.index') }}" class="transparent-button">
                    @lang('admin::app.cancel')
                </a>

                <button type="submit" class="primary-button">
                    @lang('supplier::app.admin.create.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                {{-- Name --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('supplier::app.admin.create.name')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="name"
                        :value="old('name')"
                        rules="required"
                        :label="trans('supplier::app.admin.create.name')"
                    />

                    <x-admin::form.control-group.error control-name="name" />
                </x-admin::form.control-group>

                {{-- Description --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.description')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="description"
                        :value="old('description')"
                        rows="5"
                        :label="trans('supplier::app.admin.create.description')"
                    />

                    <x-admin::form.control-group.error control-name="description" />
                </x-admin::form.control-group>

                {{-- Image --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.image')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="file"
                        name="image"
                        accept="image/*"
                        :label="trans('supplier::app.admin.create.image')"
                    />

                    <x-admin::form.control-group.error control-name="image" />
                </x-admin::form.control-group>

                {{-- Sort Order --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.sort-order')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="number"
                        name="sort_order"
                        :value="old('sort_order', 0)"
                        min="0"
                        :label="trans('supplier::app.admin.create.sort-order')"
                    />

                    <x-admin::form.control-group.error control-name="sort_order" />
                </x-admin::form.control-group>

                {{-- Contact Name --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.contact-name')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="contact_name"
                        :value="old('contact_name')"
                        :label="trans('supplier::app.admin.create.contact-name')"
                    />

                    <x-admin::form.control-group.error control-name="contact_name" />
                </x-admin::form.control-group>

                {{-- Contact Email --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.contact-email')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="email"
                        name="contact_email"
                        :value="old('contact_email')"
                        :label="trans('supplier::app.admin.create.contact-email')"
                    />

                    <x-admin::form.control-group.error control-name="contact_email" />
                </x-admin::form.control-group>

                {{-- Contact Phone --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.contact-phone')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="contact_phone"
                        :value="old('contact_phone')"
                        :label="trans('supplier::app.admin.create.contact-phone')"
                    />

                    <x-admin::form.control-group.error control-name="contact_phone" />
                </x-admin::form.control-group>

                {{-- Address --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.address')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="address"
                        :value="old('address')"
                        rows="3"
                        :label="trans('supplier::app.admin.create.address')"
                    />

                    <x-admin::form.control-group.error control-name="address" />
                </x-admin::form.control-group>

                {{-- Notes --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.notes')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="notes"
                        :value="old('notes')"
                        rows="3"
                        :label="trans('supplier::app.admin.create.notes')"
                    />

                    <x-admin::form.control-group.error control-name="notes" />
                </x-admin::form.control-group>

                {{-- Status --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.create.status')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="switch"
                        name="status"
                        value="1"
                        :checked="old('status', true)"
                        :label="trans('supplier::app.admin.create.status')"
                    />

                    <x-admin::form.control-group.error control-name="status" />
                </x-admin::form.control-group>
            </div>
        </div>
    </form>
</x-admin::layouts>
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Supplier/src/Resources/views/admin/create.blade.php
git commit -m "feat(supplier): add description, image, sort_order to create form"
```

---

## Task 10: Update Supplier Edit Form

**Files:**
- Modify: `packages/Webkul/Supplier/src/Resources/views/admin/edit.blade.php`

- [ ] **Step 1: Read existing edit form**

Run: `cat packages/Webkul/Supplier/src/Resources/views/admin/edit.blade.php`
Expected: See current form structure

- [ ] **Step 2: Add new fields with image preview to edit form**

The complete form should look like:

```blade
<x-admin::layouts>
    <x-slot:title>
        @lang('supplier::app.admin.edit.title')
    </x-slot>

    <form method="POST" action="{{ route('admin.suppliers.update', $supplier->id) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('supplier::app.admin.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.suppliers.index') }}" class="transparent-button">
                    @lang('admin::app.cancel')
                </a>

                <button type="submit" class="primary-button">
                    @lang('supplier::app.admin.edit.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                {{-- Name --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label class="required">
                        @lang('supplier::app.admin.edit.name')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="name"
                        :value="old('name', $supplier->name)"
                        rules="required"
                        :label="trans('supplier::app.admin.edit.name')"
                    />

                    <x-admin::form.control-group.error control-name="name" />
                </x-admin::form.control-group>

                {{-- Description --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.description')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="description"
                        :value="old('description', $supplier->description)"
                        rows="5"
                        :label="trans('supplier::app.admin.edit.description')"
                    />

                    <x-admin::form.control-group.error control-name="description" />
                </x-admin::form.control-group>

                {{-- Image --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.image')
                    </x-admin::form.control-group.label>

                    @if($supplier->image)
                        <div class="mb-4">
                            <img src="{{ Storage::url($supplier->image) }}"
                                 alt="{{ $supplier->name }}"
                                 class="w-32 h-32 object-cover rounded border">
                        </div>

                        <div class="mb-4">
                            <input type="checkbox"
                                   name="remove_image"
                                   id="remove_image"
                                   value="1"
                                   class="mr-2">
                            <label for="remove_image" class="cursor-pointer">
                                @lang('supplier::app.admin.edit.remove-image')
                            </label>
                        </div>
                    @endif

                    <x-admin::form.control-group.control
                        type="file"
                        name="image"
                        accept="image/*"
                        :label="trans('supplier::app.admin.edit.image')"
                    />

                    <x-admin::form.control-group.error control-name="image" />
                </x-admin::form.control-group>

                {{-- Sort Order --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.sort-order')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="number"
                        name="sort_order"
                        :value="old('sort_order', $supplier->sort_order ?? 0)"
                        min="0"
                        :label="trans('supplier::app.admin.edit.sort-order')"
                    />

                    <x-admin::form.control-group.error control-name="sort_order" />
                </x-admin::form.control-group>

                {{-- Contact Name --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.contact-name')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="contact_name"
                        :value="old('contact_name', $supplier->contact_name)"
                        :label="trans('supplier::app.admin.edit.contact-name')"
                    />

                    <x-admin::form.control-group.error control-name="contact_name" />
                </x-admin::form.control-group>

                {{-- Contact Email --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.contact-email')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="email"
                        name="contact_email"
                        :value="old('contact_email', $supplier->contact_email)"
                        :label="trans('supplier::app.admin.edit.contact-email')"
                    />

                    <x-admin::form.control-group.error control-name="contact_email" />
                </x-admin::form.control-group>

                {{-- Contact Phone --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.contact-phone')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="contact_phone"
                        :value="old('contact_phone', $supplier->contact_phone)"
                        :label="trans('supplier::app.admin.edit.contact-phone')"
                    />

                    <x-admin::form.control-group.error control-name="contact_phone" />
                </x-admin::form.control-group>

                {{-- Address --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.address')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="address"
                        :value="old('address', $supplier->address)"
                        rows="3"
                        :label="trans('supplier::app.admin.edit.address')"
                    />

                    <x-admin::form.control-group.error control-name="address" />
                </x-admin::form.control-group>

                {{-- Notes --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.notes')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        name="notes"
                        :value="old('notes', $supplier->notes)"
                        rows="3"
                        :label="trans('supplier::app.admin.edit.notes')"
                    />

                    <x-admin::form.control-group.error control-name="notes" />
                </x-admin::form.control-group>

                {{-- Status --}}
                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        @lang('supplier::app.admin.edit.status')
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="switch"
                        name="status"
                        value="1"
                        :checked="old('status', $supplier->status)"
                        :label="trans('supplier::app.admin.edit.status')"
                    />

                    <x-admin::form.control-group.error control-name="status" />
                </x-admin::form.control-group>
            </div>
        </div>
    </form>
</x-admin::layouts>
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Supplier/src/Resources/views/admin/edit.blade.php
git commit -m "feat(supplier): add description, image preview, sort_order to edit form"
```

---

## Task 11: Update ProductDataGrid with Supplier Filter

**Files:**
- Modify: `packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php`

- [ ] **Step 1: Read current ProductDataGrid to understand structure**

Run: `head -150 packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php`
Expected: See query builder and column definitions

- [ ] **Step 2: Add supplier LEFT JOIN to query builder**

Find the `prepareQueryBuilder()` method and add supplier join after existing joins:

```php
->leftJoin('suppliers', 'product_flat.supplier_id', '=', 'suppliers.id')
```

Add to SELECT:
```php
->addSelect('suppliers.name as supplier_name')
```

Add filter mapping after other `addFilter` calls:
```php
$this->addFilter('supplier_id', 'product_flat.supplier_id');
```

- [ ] **Step 3: Add supplier_name column to prepareColumns()**

Add after the `attribute_family` column definition:

```php
$this->addColumn([
    'index' => 'supplier_name',
    'label' => trans('admin::app.catalog.products.index.datagrid.supplier'),
    'type' => 'string',
    'searchable' => true,
    'filterable' => true,
    'sortable' => true,
    'closure' => fn ($row) => $row->supplier_name ?? '—',
]);
```

- [ ] **Step 4: Run code formatter**

Run: `vendor/bin/pint packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php`
Expected: Code formatted

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php
git commit -m "feat(product): add supplier filter and column to ProductDataGrid"
```

---

## Task 12: Add Product Translations for Supplier Field

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/lang/en/app.php`
- Modify: `packages/Webkul/Admin/src/Resources/lang/ru/app.php`

- [ ] **Step 1: Find catalog.products section in English translations**

Run: `grep -n "catalog.products" packages/Webkul/Admin/src/Resources/lang/en/app.php | head -5`
Expected: Find line numbers for products translations

- [ ] **Step 2: Add supplier translation keys to English file**

Find the `'catalog' => ['products' => ['index' => ['datagrid' => [` section and add:

```php
'supplier' => 'Supplier',
```

Find the `'edit' =>` section within products and add:

```php
'supplier' => 'Supplier',
'select-supplier' => '— Select Supplier —',
```

Find the `'create' =>` section and add:

```php
'supplier' => 'Supplier',
'select-supplier' => '— Select Supplier —',
```

- [ ] **Step 3: Add supplier translation keys to Russian file**

Find the same sections in Russian file and add:

In `'index' => ['datagrid' => [`:
```php
'supplier' => 'Поставщик',
```

In `'edit' =>`:
```php
'supplier' => 'Поставщик',
'select-supplier' => '— Выберите поставщика —',
```

In `'create' =>`:
```php
'supplier' => 'Поставщик',
'select-supplier' => '— Выберите поставщика —',
```

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/lang/en/app.php packages/Webkul/Admin/src/Resources/lang/ru/app.php
git commit -m "feat(product): add translations for supplier field"
```

---

## Task 13: Add Supplier Dropdown to Product Edit Form

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php`

- [ ] **Step 1: Find where to add supplier dropdown**

Run: `grep -n "attribute_family\|categories" packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php | head -20`
Expected: Find line numbers for organization section

- [ ] **Step 2: Add supplier dropdown after categories field**

Add this block after the categories selection field:

```blade
{{-- Supplier --}}
<x-admin::form.control-group>
    <x-admin::form.control-group.label>
        @lang('admin::app.catalog.products.edit.supplier')
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="select"
        name="supplier_id"
        :value="old('supplier_id', $product->supplier_id ?? '')"
        :label="trans('admin::app.catalog.products.edit.supplier')"
    >
        <option value="">
            @lang('admin::app.catalog.products.edit.select-supplier')
        </option>

        @foreach(app('Webkul\Supplier\Repositories\SupplierRepository')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->all() as $supplier)
            <option value="{{ $supplier->id }}" {{ old('supplier_id', $product->supplier_id ?? '') == $supplier->id ? 'selected' : '' }}>
                {{ $supplier->name }}
            </option>
        @endforeach
    </x-admin::form.control-group.control>

    <x-admin::form.control-group.error control-name="supplier_id" />
</x-admin::form.control-group>
```

- [ ] **Step 3: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php
git commit -m "feat(product): add supplier dropdown to product edit form"
```

---

## Task 14: Write Supplier Enhancement Tests

**Files:**
- Create: `packages/Webkul/Supplier/tests/Feature/SupplierEnhancementsTest.php`

- [ ] **Step 1: Create test file**

```bash
touch packages/Webkul/Supplier/tests/Feature/SupplierEnhancementsTest.php
```

- [ ] **Step 2: Write comprehensive feature tests**

```php
<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\Product;
use Webkul\Supplier\Models\Supplier;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function () {
    $this->admin = createAdmin();
    actingAs($this->admin, 'admin');
    Storage::fake('public');
});

test('supplier can be created with all fields including image', function () {
    $image = UploadedFile::fake()->image('supplier.jpg', 100, 100);

    $response = post(route('admin.suppliers.store'), [
        'name' => 'Test Supplier',
        'description' => 'Test description',
        'image' => $image,
        'sort_order' => 10,
        'contact_name' => 'John Doe',
        'contact_email' => 'john@example.com',
        'contact_phone' => '+1234567890',
        'address' => '123 Test St',
        'notes' => 'Test notes',
        'status' => true,
    ]);

    $response->assertRedirect(route('admin.suppliers.index'));

    assertDatabaseHas('suppliers', [
        'name' => 'Test Supplier',
        'description' => 'Test description',
        'sort_order' => 10,
        'contact_name' => 'John Doe',
        'status' => true,
    ]);

    $supplier = Supplier::where('name', 'Test Supplier')->first();
    expect($supplier->image)->not->toBeNull();
    Storage::disk('public')->assertExists($supplier->image);
});

test('supplier image can be updated', function () {
    $supplier = Supplier::factory()->create([
        'name' => 'Test Supplier',
        'image' => null,
    ]);

    $image = UploadedFile::fake()->image('new-supplier.jpg', 100, 100);

    $response = put(route('admin.suppliers.update', $supplier->id), [
        'name' => 'Test Supplier Updated',
        'description' => 'Updated description',
        'image' => $image,
        'sort_order' => 5,
        'status' => true,
    ]);

    $response->assertRedirect(route('admin.suppliers.index'));

    $supplier->refresh();
    expect($supplier->name)->toBe('Test Supplier Updated');
    expect($supplier->description)->toBe('Updated description');
    expect($supplier->sort_order)->toBe(5);
    expect($supplier->image)->not->toBeNull();
    Storage::disk('public')->assertExists($supplier->image);
});

test('supplier image can be removed via checkbox', function () {
    $image = UploadedFile::fake()->image('supplier.jpg', 100, 100);
    $imagePath = $image->store('supplier_images', 'public');

    $supplier = Supplier::factory()->create([
        'name' => 'Test Supplier',
        'image' => $imagePath,
    ]);

    Storage::disk('public')->assertExists($imagePath);

    $response = put(route('admin.suppliers.update', $supplier->id), [
        'name' => 'Test Supplier',
        'remove_image' => true,
        'status' => true,
    ]);

    $response->assertRedirect(route('admin.suppliers.index'));

    $supplier->refresh();
    expect($supplier->image)->toBeNull();
    Storage::disk('public')->assertMissing($imagePath);
});

test('supplier image is replaced when new image uploaded', function () {
    $oldImage = UploadedFile::fake()->image('old-supplier.jpg', 100, 100);
    $oldImagePath = $oldImage->store('supplier_images', 'public');

    $supplier = Supplier::factory()->create([
        'name' => 'Test Supplier',
        'image' => $oldImagePath,
    ]);

    Storage::disk('public')->assertExists($oldImagePath);

    $newImage = UploadedFile::fake()->image('new-supplier.jpg', 100, 100);

    $response = put(route('admin.suppliers.update', $supplier->id), [
        'name' => 'Test Supplier',
        'image' => $newImage,
        'status' => true,
    ]);

    $response->assertRedirect(route('admin.suppliers.index'));

    $supplier->refresh();
    expect($supplier->image)->not->toBe($oldImagePath);
    Storage::disk('public')->assertMissing($oldImagePath);
    Storage::disk('public')->assertExists($supplier->image);
});

test('supplier deletion is prevented when has products', function () {
    $supplier = Supplier::factory()->create();
    $product = Product::factory()->create([
        'supplier_id' => $supplier->id,
    ]);

    $response = delete(route('admin.suppliers.destroy', $supplier->id));

    $response->assertStatus(400);
    expect($response->json('message'))->toContain('Cannot delete supplier');

    assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
    ]);
});

test('supplier deletion succeeds when no products associated', function () {
    $image = UploadedFile::fake()->image('supplier.jpg', 100, 100);
    $imagePath = $image->store('supplier_images', 'public');

    $supplier = Supplier::factory()->create([
        'image' => $imagePath,
    ]);

    Storage::disk('public')->assertExists($imagePath);

    $response = delete(route('admin.suppliers.destroy', $supplier->id));

    $response->assertOk();
    expect(Supplier::find($supplier->id))->toBeNull();
    Storage::disk('public')->assertMissing($imagePath);
});

test('supplier datagrid shows products count', function () {
    $supplier = Supplier::factory()->create([
        'name' => 'Test Supplier',
        'sort_order' => 10,
    ]);

    Product::factory()->count(3)->create([
        'supplier_id' => $supplier->id,
    ]);

    $response = get(route('admin.suppliers.index'), [
        'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();
    $data = $response->json();

    $supplierRow = collect($data['records'])->firstWhere('supplier_id', $supplier->id);
    expect($supplierRow)->not->toBeNull();
    expect($supplierRow['products_count'])->toBe(3);
});

test('supplier datagrid sorts by sort_order then name', function () {
    Supplier::factory()->create(['name' => 'Zebra Supplier', 'sort_order' => 10]);
    Supplier::factory()->create(['name' => 'Alpha Supplier', 'sort_order' => 5]);
    Supplier::factory()->create(['name' => 'Beta Supplier', 'sort_order' => 5]);

    $response = get(route('admin.suppliers.index'), [
        'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();
    $data = $response->json();

    $names = collect($data['records'])->pluck('name')->toArray();
    expect($names[0])->toBe('Alpha Supplier');
    expect($names[1])->toBe('Beta Supplier');
    expect($names[2])->toBe('Zebra Supplier');
});

test('product datagrid shows supplier name', function () {
    $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
    $product = Product::factory()->create([
        'supplier_id' => $supplier->id,
    ]);

    $response = get(route('admin.catalog.products.index'), [
        'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();
    $data = $response->json();

    $productRow = collect($data['records'])->firstWhere('product_id', $product->id);
    expect($productRow)->not->toBeNull();
    expect($productRow['supplier_name'])->toBe('Test Supplier');
});

test('product datagrid filters by supplier', function () {
    $supplier1 = Supplier::factory()->create(['name' => 'Supplier 1']);
    $supplier2 = Supplier::factory()->create(['name' => 'Supplier 2']);

    $product1 = Product::factory()->create(['supplier_id' => $supplier1->id]);
    $product2 = Product::factory()->create(['supplier_id' => $supplier2->id]);
    $product3 = Product::factory()->create(['supplier_id' => null]);

    $response = get(route('admin.catalog.products.index', [
        'filters' => ['supplier_id' => $supplier1->id],
    ]), [
        'HTTP_X-Requested-With' => 'XMLHttpRequest',
    ]);

    $response->assertOk();
    $data = $response->json();

    $productIds = collect($data['records'])->pluck('product_id')->toArray();
    expect($productIds)->toContain($product1->id);
    expect($productIds)->not->toContain($product2->id);
    expect($productIds)->not->toContain($product3->id);
});

test('image validation rejects non-image files', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $response = post(route('admin.suppliers.store'), [
        'name' => 'Test Supplier',
        'image' => $file,
        'status' => true,
    ]);

    $response->assertSessionHasErrors('image');
});

test('image validation rejects oversized images', function () {
    $image = UploadedFile::fake()->image('large-supplier.jpg', 3000, 3000)->size(3000);

    $response = post(route('admin.suppliers.store'), [
        'name' => 'Test Supplier',
        'image' => $image,
        'status' => true,
    ]);

    $response->assertSessionHasErrors('image');
});
```

- [ ] **Step 3: Run tests**

Run: `php artisan test --filter=SupplierEnhancementsTest --compact`
Expected: All tests pass

- [ ] **Step 4: Commit**

```bash
git add packages/Webkul/Supplier/tests/Feature/SupplierEnhancementsTest.php
git commit -m "test(supplier): add comprehensive tests for enhancements"
```

---

## Task 15: Run Full Test Suite

**Files:**
- None (validation step)

- [ ] **Step 1: Run all Supplier tests**

Run: `php artisan test packages/Webkul/Supplier/tests --compact`
Expected: All tests pass

- [ ] **Step 2: Run code formatter on all changed files**

Run: `vendor/bin/pint packages/Webkul/Supplier packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php`
Expected: All files formatted

- [ ] **Step 3: Clear caches**

```bash
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan cache:clear
```

Expected: Caches cleared successfully

- [ ] **Step 4: Verify storage link exists**

Run: `php artisan storage:link`
Expected: Symbolic link created or already exists

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "chore: format code and update caches for supplier enhancements"
```

---

## Post-Implementation Verification

After all tasks complete:

1. **Manual UI Testing:**
   - [ ] Navigate to Suppliers list (`/admin/suppliers`)
   - [ ] Verify image thumbnails display
   - [ ] Verify products count shows with clickable link
   - [ ] Click products count link, verify filter applies
   - [ ] Create new supplier with all fields
   - [ ] Upload an image, verify preview
   - [ ] Edit supplier, replace image
   - [ ] Edit supplier, remove image via checkbox
   - [ ] Verify sort_order affects list ordering
   - [ ] Try to delete supplier with products (should fail)
   - [ ] Delete supplier with no products (should succeed)
   - [ ] Navigate to Products list
   - [ ] Verify supplier name column appears
   - [ ] Use supplier filter dropdown
   - [ ] Edit product, assign supplier
   - [ ] Verify validation errors work (oversized image, wrong format)

2. **Database Verification:**
   - [ ] Check `suppliers` table has new columns
   - [ ] Check products have `supplier_id` foreign key
   - [ ] Verify indexes exist on `products.supplier_id` and `suppliers.sort_order`

3. **Performance Check:**
   - [ ] Test DataGrid load time with 100+ suppliers
   - [ ] Test DataGrid load time with 1000+ products
   - [ ] Verify no N+1 queries in DataGrid

4. **Translations Check:**
   - [ ] Switch to English locale, verify all labels
   - [ ] Switch to Russian locale, verify all labels

---

## Summary

This plan implements supplier enhancements with:
- ✅ Database migration for `description`, `image`, `sort_order` fields
- ✅ Image upload/delete/replacement handling
- ✅ Supplier deletion protection when products exist
- ✅ Enhanced SupplierDataGrid with products count and image
- ✅ Product DataGrid supplier filter and column
- ✅ Supplier dropdown in product forms
- ✅ Complete English and Russian translations
- ✅ Comprehensive test coverage
- ✅ Error handling and logging

**Total Tasks:** 15
**Estimated Time:** 2-3 hours for implementation + testing
