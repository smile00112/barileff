# Supplier Module Enhancements Design

**Date:** 2026-04-08
**Status:** Draft
**Author:** Claude (Sonnet 4.5)

## Overview

Enhance the existing Supplier package in Bagisto to include image uploads, descriptions, custom sorting, and improved product integration. The Supplier module allows managing suppliers and associating products with a single supplier.

## Requirements

### Core Features
1. Suppliers stored in separate `suppliers` table with these fields:
   - Name (existing)
   - Description (new)
   - Image/logo (new)
   - Sort order (new)
   - Contact information (existing: contact_name, contact_email, contact_phone, address)
   - Notes (existing)
   - Status (existing: active/inactive)

2. Supplier list page displays:
   - Image thumbnail
   - Name
   - Product count
   - Sort order
   - Status
   - Actions (edit, delete)
   - Link to filtered product list

3. Product integration:
   - Each product can have one supplier (or none)
   - `supplier_id` foreign key on products table (already exists)
   - Supplier dropdown in product create/edit forms
   - Supplier filter in ProductDataGrid
   - Direct link from supplier list to filtered products

## Architecture Decisions

### Approach: Incremental Migration with On-the-Fly Aggregation

**Selected:** Approach A - Simple incremental migration with SQL JOIN for product counts

**Rationale:**
- Follows Bagisto's existing DataGrid patterns (ProductDataGrid already uses JOINs)
- No cache synchronization complexity
- Suitable for typical e-commerce catalog sizes
- Easy to test and maintain
- No risk of count drift from cache desync

**Rejected Alternatives:**
- Cached count column: Added complexity, potential sync issues
- Database views: Different behavior across MySQL/PostgreSQL

## Database Schema

### Migration: `2026_04_08_000001_add_supplier_fields_to_suppliers_table`

Adds three new columns to existing `suppliers` table:

```php
Schema::table('suppliers', function (Blueprint $table) {
    $table->text('description')->nullable()->after('name');
    $table->string('image')->nullable()->after('description');
    $table->integer('sort_order')->default(0)->after('image');
});
```

**Field Specifications:**
- `description` (text, nullable) — full supplier description
- `image` (string, nullable) — file path to supplier logo (storage path)
- `sort_order` (integer, default 0) — manual ordering, lower numbers first

**Existing fields retained:**
- `name`, `contact_name`, `contact_email`, `contact_phone`, `address`, `notes`, `status`, `timestamps`

### Products Table
No changes needed - `supplier_id` foreign key already exists from prior migration.

## Component Updates

### 1. Supplier Model (`Webkul\Supplier\Models\Supplier`)

**Updates:**
```php
protected $fillable = [
    'name',
    'description',      // NEW
    'image',            // NEW
    'sort_order',       // NEW
    'contact_name',
    'contact_email',
    'contact_phone',
    'address',
    'notes',
    'status',
];
```

**New Method:**
```php
public function productsCount(): int
{
    return $this->products()->count();
}
```

**Existing Relationship (unchanged):**
```php
public function products(): HasMany
{
    return $this->hasMany(ProductProxy::modelClass(), 'supplier_id');
}
```

### 2. SupplierRepository (`Webkul\Supplier\Repositories\SupplierRepository`)

**New Methods:**

```php
/**
 * Create supplier with image upload handling
 */
public function create(array $data): Supplier
{
    if (isset($data['image'])) {
        $data['image'] = $this->uploadImage($data['image']);
    }

    return parent::create($data);
}

/**
 * Update supplier with image replacement handling
 */
public function update(array $data, $id): Supplier
{
    $supplier = $this->findOrFail($id);

    // Handle image replacement
    if (isset($data['image'])) {
        $this->deleteImage($supplier->image);
        $data['image'] = $this->uploadImage($data['image']);
    }

    // Handle image removal
    if (isset($data['remove_image']) && $data['remove_image']) {
        $this->deleteImage($supplier->image);
        $data['image'] = null;
    }

    return parent::update($data, $id);
}

/**
 * Delete supplier with image cleanup
 */
public function delete($id): bool
{
    $supplier = $this->findOrFail($id);

    // Prevent deletion if supplier has products
    if ($supplier->products()->exists()) {
        throw new \Exception(
            trans('supplier::app.admin.delete-failed', [
                'count' => $supplier->products()->count()
            ])
        );
    }

    $this->deleteImage($supplier->image);

    return parent::delete($id);
}

/**
 * Upload supplier image
 */
protected function uploadImage($image): string
{
    return $image->store('supplier_images', 'public');
}

/**
 * Delete supplier image file
 */
protected function deleteImage(?string $path): void
{
    if ($path && Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }
}
```

**Image Storage:**
- Directory: `storage/app/public/supplier_images/`
- Public access via: `storage/supplier_images/`
- Requires: `php artisan storage:link` (standard Bagisto setup)

### 3. SupplierDataGrid (`Webkul\Supplier\DataGrids\SupplierDataGrid`)

**Query Builder:**
```php
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
```

**Columns:**
1. `supplier_id` — integer, sortable, filterable
2. `image` — image thumbnail (50x50), with placeholder fallback
3. `name` — string, searchable, sortable
4. `products_count` — integer, sortable, with clickable link
5. `sort_order` — integer, sortable, filterable
6. `status` — boolean badge (active/inactive)

**Products Count Column:**
```php
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
                'filters[supplier_id]' => $row->supplier_id
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
```

**Image Column:**
```php
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
```

**Actions:** Retain existing Edit and Delete actions

### 4. ProductDataGrid (`Webkul\Admin\DataGrids\Catalog\ProductDataGrid`)

**Query Builder Updates:**
```php
->leftJoin('suppliers', 'product_flat.supplier_id', '=', 'suppliers.id')
->addSelect('suppliers.name as supplier_name')
```

**Filter Mapping:**
```php
$this->addFilter('supplier_id', 'product_flat.supplier_id');
```

**New Column:**
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

**Dropdown Filter:**
Add to `prepareColumns()` or via filter configuration:
```php
$this->addFilter([
    'type' => 'dropdown',
    'index' => 'supplier_id',
    'label' => trans('admin::app.catalog.products.index.datagrid.supplier'),
    'options' => function () {
        return app(SupplierRepository::class)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->prepend(trans('admin::app.catalog.products.index.datagrid.all-suppliers'), '');
    },
]);
```

**URL Parameter Support:**
Automatically handled via `filters[supplier_id]` query parameter when clicking from SupplierDataGrid.

### 5. SupplierController (`Webkul\Supplier\Http\Controllers\Admin\SupplierController`)

**Updated Methods:**
```php
public function store(SupplierRequest $request): RedirectResponse
{
    try {
        $this->supplierRepository->create($request->validated());

        session()->flash('success', trans('supplier::app.admin.created'));
    } catch (\Exception $e) {
        Log::error('Supplier creation failed', [
            'error' => $e->getMessage(),
            'data' => $request->validated(),
        ]);

        session()->flash('error', trans('supplier::app.admin.create-failed'));
    }

    return redirect()->route('admin.suppliers.index');
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

        session()->flash('error', trans('supplier::app.admin.update-failed'));
    }

    return redirect()->route('admin.suppliers.index');
}

public function destroy(int $id): JsonResponse
{
    try {
        $this->supplierRepository->delete($id);

        return new JsonResponse([
            'message' => trans('supplier::app.admin.deleted')
        ]);
    } catch (\Exception $e) {
        Log::error('Supplier deletion failed', [
            'supplier_id' => $id,
            'error' => $e->getMessage(),
        ]);

        return new JsonResponse([
            'message' => $e->getMessage()
        ], 400);
    }
}
```

### 6. SupplierRequest (`Webkul\Supplier\Http\Requests\SupplierRequest`)

**Validation Rules:**
```php
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'remove_image' => 'nullable|boolean',
        'sort_order' => 'nullable|integer|min:0',
        'contact_name' => 'nullable|string|max:255',
        'contact_email' => 'nullable|email|max:255',
        'contact_phone' => 'nullable|string|max:50',
        'address' => 'nullable|string',
        'notes' => 'nullable|string',
        'status' => 'boolean',
    ];
}
```

### 7. Supplier Forms (Create/Edit Views)

**Location:**
- `packages/Webkul/Supplier/src/Resources/views/admin/create.blade.php`
- `packages/Webkul/Supplier/src/Resources/views/admin/edit.blade.php`

**New Form Fields (after `name` field):**

**Description:**
```blade
<x-admin::form.control-group>
    <x-admin::form.control-group.label>
        @lang('supplier::app.admin.create.description')
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="textarea"
        name="description"
        :value="old('description', $supplier->description ?? '')"
        rows="5"
    />

    <x-admin::form.control-group.error control-name="description" />
</x-admin::form.control-group>
```

**Image Upload:**
```blade
<x-admin::form.control-group>
    <x-admin::form.control-group.label>
        @lang('supplier::app.admin.create.image')
    </x-admin::form.control-group.label>

    @if(isset($supplier) && $supplier->image)
        <div class="mb-2">
            <img src="{{ Storage::url($supplier->image) }}"
                 alt="{{ $supplier->name }}"
                 class="w-32 h-32 object-cover rounded border">
        </div>

        <x-admin::form.control-group>
            <input type="checkbox"
                   name="remove_image"
                   id="remove_image"
                   value="1"
                   class="mr-2">
            <label for="remove_image">
                @lang('supplier::app.admin.edit.remove-image')
            </label>
        </x-admin::form.control-group>
    @endif

    <x-admin::form.control-group.control
        type="file"
        name="image"
        accept="image/*"
    />

    <x-admin::form.control-group.error control-name="image" />
</x-admin::form.control-group>
```

**Sort Order:**
```blade
<x-admin::form.control-group>
    <x-admin::form.control-group.label>
        @lang('supplier::app.admin.create.sort-order')
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="number"
        name="sort_order"
        :value="old('sort_order', $supplier->sort_order ?? 0)"
        min="0"
    />

    <x-admin::form.control-group.error control-name="sort_order" />
</x-admin::form.control-group>
```

### 8. Product Form Integration

**Location:** `packages/Webkul/Admin/src/Resources/views/catalog/products/edit.blade.php`

**Add Supplier Dropdown:**
Position after category/attribute family selectors in product details section.

```blade
<x-admin::form.control-group>
    <x-admin::form.control-group.label>
        @lang('admin::app.catalog.products.edit.supplier')
    </x-admin::form.control-group.label>

    <x-admin::form.control-group.control
        type="select"
        name="supplier_id"
        :value="old('supplier_id', $product->supplier_id ?? '')"
    >
        <option value="">
            @lang('admin::app.catalog.products.edit.select-supplier')
        </option>

        @foreach(app('Webkul\Supplier\Repositories\SupplierRepository')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->all() as $supplier)
            <option value="{{ $supplier->id }}">
                {{ $supplier->name }}
            </option>
        @endforeach
    </x-admin::form.control-group.control>

    <x-admin::form.control-group.error control-name="supplier_id" />
</x-admin::form.control-group>
```

**ProductController Updates:**
Add to product validation and mass assignment - `supplier_id` should already be in the products table migration.

**Product Model:**
Ensure `supplier_id` is in `$fillable` array (likely already there).

## Translations

### Supplier Package (`supplier::app.admin`)

**English (`en/app.php`):**
```php
'datagrid' => [
    'id' => 'ID',
    'name' => 'Name',
    'image' => 'Image',
    'products-count' => 'Products',
    'sort-order' => 'Sort Order',
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'edit' => 'Edit',
    'delete' => 'Delete',
],

'create' => [
    'description' => 'Description',
    'image' => 'Image',
    'sort-order' => 'Sort Order',
],

'edit' => [
    'description' => 'Description',
    'image' => 'Image',
    'sort-order' => 'Sort Order',
    'remove-image' => 'Remove current image',
],

'delete-failed' => 'Cannot delete supplier. It has :count products associated.',
'create-failed' => 'Failed to create supplier. Please try again.',
'update-failed' => 'Failed to update supplier. Please try again.',
```

**Russian (`ru/app.php`):**
```php
'datagrid' => [
    'id' => 'ID',
    'name' => 'Название',
    'image' => 'Изображение',
    'products-count' => 'Товары',
    'sort-order' => 'Сортировка',
    'status' => 'Статус',
    'active' => 'Активен',
    'inactive' => 'Неактивен',
    'edit' => 'Редактировать',
    'delete' => 'Удалить',
],

'create' => [
    'description' => 'Описание',
    'image' => 'Изображение',
    'sort-order' => 'Порядок сортировки',
],

'edit' => [
    'description' => 'Описание',
    'image' => 'Изображение',
    'sort-order' => 'Порядок сортировки',
    'remove-image' => 'Удалить текущее изображение',
],

'delete-failed' => 'Невозможно удалить поставщика. С ним связано товаров: :count.',
'create-failed' => 'Не удалось создать поставщика. Попробуйте снова.',
'update-failed' => 'Не удалось обновить поставщика. Попробуйте снова.',
```

### Admin Package (`admin::app.catalog.products`)

**English:**
```php
'index' => [
    'datagrid' => [
        'supplier' => 'Supplier',
        'filter' => [
            'supplier' => 'Filter by Supplier',
            'all-suppliers' => '— All Suppliers —',
        ],
    ],
],

'edit' => [
    'supplier' => 'Supplier',
    'select-supplier' => '— Select Supplier —',
],

'create' => [
    'supplier' => 'Supplier',
    'select-supplier' => '— Select Supplier —',
],
```

**Russian:**
```php
'index' => [
    'datagrid' => [
        'supplier' => 'Поставщик',
        'filter' => [
            'supplier' => 'Фильтр по поставщику',
            'all-suppliers' => '— Все поставщики —',
        ],
    ],
],

'edit' => [
    'supplier' => 'Поставщик',
    'select-supplier' => '— Выберите поставщика —',
],

'create' => [
    'supplier' => 'Поставщик',
    'select-supplier' => '— Выберите поставщика —',
],
```

## Testing Strategy

### Pest Tests

**Location:** `packages/Webkul/Supplier/tests/Feature/SupplierTest.php`

**Test Cases:**
1. `test_supplier_list_displays_with_product_counts`
2. `test_supplier_creation_with_all_fields`
3. `test_supplier_image_upload`
4. `test_supplier_image_removal`
5. `test_supplier_image_replacement`
6. `test_supplier_update_without_image_change`
7. `test_supplier_deletion_prevented_when_has_products`
8. `test_supplier_deletion_succeeds_when_no_products`
9. `test_supplier_datagrid_sorting_by_sort_order`
10. `test_supplier_datagrid_products_count_accuracy`
11. `test_supplier_validation_rules`

**Location:** `packages/Webkul/Admin/tests/Feature/Catalog/ProductTest.php`

**Additional Test Cases:**
1. `test_product_creation_with_supplier`
2. `test_product_update_with_supplier_change`
3. `test_product_datagrid_supplier_filter`
4. `test_product_datagrid_supplier_filter_url_parameter`
5. `test_product_form_displays_supplier_dropdown`

### Manual Testing Checklist

- [ ] Create supplier with all fields (description, image, sort_order)
- [ ] Upload different image formats (jpg, png, webp)
- [ ] Verify image thumbnail displays in supplier list
- [ ] Edit supplier and replace image
- [ ] Edit supplier and remove image via checkbox
- [ ] Verify sort_order affects list ordering
- [ ] Create products and assign to suppliers
- [ ] Verify products_count column accuracy
- [ ] Click products_count link, verify filter applies
- [ ] Use supplier dropdown filter in product list
- [ ] Attempt to delete supplier with products (should fail)
- [ ] Delete supplier with no products (should succeed)
- [ ] Verify image file is deleted from storage
- [ ] Test validation errors for invalid image types
- [ ] Test validation errors for oversized images (>2MB)
- [ ] Test supplier selection in product create/edit forms

## Error Handling

### 1. Supplier Deletion with Associated Products

**Behavior:** Prevent deletion if supplier has any associated products.

**Implementation:**
```php
if ($supplier->products()->exists()) {
    throw new \Exception(
        trans('supplier::app.admin.delete-failed', [
            'count' => $supplier->products()->count()
        ])
    );
}
```

**User Experience:**
- Error message displayed: "Cannot delete supplier X. It has Y products associated."
- Suggest unlinking products first or selecting a different supplier

### 2. Image Upload Failures

**Scenarios:**
- Storage permission issues
- Disk full
- Invalid file format
- File size exceeds limit

**Implementation:**
```php
try {
    $data['image'] = $this->uploadImage($data['image']);
} catch (\Exception $e) {
    Log::error('Supplier image upload failed', [
        'error' => $e->getMessage(),
        'supplier' => $data['name'] ?? 'new',
    ]);

    throw new \Exception(trans('supplier::app.admin.image-upload-failed'));
}
```

**User Experience:**
- Clear error message: "Failed to upload image. Please try again."
- Form retains all other field values
- Log contains debugging details for administrators

### 3. Invalid Supplier Reference in Products

**Scenario:** Supplier is deleted while product edit form is open

**Validation:**
```php
'supplier_id' => 'nullable|exists:suppliers,id',
```

**User Experience:**
- Validation error: "The selected supplier is invalid."
- Product form redisplays with error highlight
- User selects a different supplier

### 4. Concurrent Modifications

**Scenario:** Two administrators edit same supplier simultaneously

**Approach:** Last-write-wins (Laravel default)

**Note:** If concurrency control is needed later, consider adding `updated_at` optimistic locking.

## Performance Considerations

### DataGrid Performance

**Products Count Aggregation:**
- Uses standard SQL COUNT with GROUP BY
- Performance acceptable for <100k products
- If performance issues arise, consider:
  - Database indexing on `products.supplier_id`
  - Query optimization via eager loading
  - Caching layer for frequently accessed suppliers

**Indexes:**
Ensure these indexes exist:
```sql
CREATE INDEX idx_products_supplier_id ON products(supplier_id);
CREATE INDEX idx_suppliers_sort_order ON suppliers(sort_order);
```

### Image Storage

**Considerations:**
- Images stored locally in `storage/app/public/`
- For high-traffic sites, consider CDN integration
- Recommended: resize/optimize images on upload (future enhancement)
- Current limit: 2MB per image

## Migration Path

### Deployment Steps

1. **Run Migration:**
   ```bash
   php artisan migrate
   ```

2. **Verify Storage Link:**
   ```bash
   php artisan storage:link
   ```

3. **Clear Caches:**
   ```bash
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

4. **Run Tests:**
   ```bash
   php artisan test --testsuite="Supplier Feature Test"
   ```

5. **Format Code:**
   ```bash
   vendor/bin/pint
   ```

### Rollback Strategy

If issues arise:
```bash
php artisan migrate:rollback --step=1
```

This removes the new columns. Existing data in other columns remains intact.

## Future Enhancements

Potential improvements not in current scope:

1. **Supplier Performance Analytics**
   - Track delivery times
   - Quality ratings
   - Order fulfillment metrics

2. **Multi-Supplier Products**
   - Allow products to have multiple suppliers
   - Select preferred supplier per order

3. **Supplier Portal**
   - Allow suppliers to log in
   - View their products
   - Update inventory

4. **Bulk Product Assignment**
   - Assign multiple products to supplier at once
   - Import supplier assignments via CSV

5. **Image Optimization**
   - Auto-resize uploaded images
   - Generate thumbnails
   - WebP conversion for performance

## Summary

This design enhances the existing Supplier module with:
- Rich supplier profiles (description, image, custom sorting)
- Visual supplier list with product counts
- Seamless product integration (dropdown selection, dataGrid filter)
- Robust error handling and validation
- Comprehensive test coverage

The implementation follows Bagisto's architectural patterns, uses existing repositories and DataGrid infrastructure, and maintains backward compatibility with the current supplier_id foreign key on products.
