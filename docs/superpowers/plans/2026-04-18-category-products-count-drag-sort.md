# Category List: Products Count Column + Drag-and-Drop Sorting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a "products count" column (with link to filtered products) and drag-and-drop sibling reordering to the admin category tree view.

**Architecture:** The category index page renders a `v-category-tree` Vue component (not the DataGrid). We add a `productCounts` prop (from a single bulk DB query in the controller) and refactor the flat renderer to two recursive components — `v-category-tree` (root, holds state) and `v-category-level` (recursive, renders one sibling group in a `<draggable>`). A new `POST /categories/reorder` endpoint persists position changes.

**Tech Stack:** Laravel 11, PHP 8.2, Pest 3 (tests), Vue 3 (inline options API), vuedraggable v4 (globally registered as `<draggable>`)

---

## File Map

| File | Action | What changes |
|---|---|---|
| `packages/Webkul/Admin/src/Resources/lang/en/app.php` | Modify | Add `reorder-success` key |
| `packages/Webkul/Admin/src/Routes/catalog-routes.php` | Modify | Add `POST categories/reorder` route |
| `packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php` | Modify | Add `reorder()` method; add `$productCounts` query to `index()` |
| `packages/Webkul/Admin/src/Resources/views/catalog/categories/index.blade.php` | Rewrite | Add Products column header/cell; split into `v-category-tree` + `v-category-level` with `<draggable>` |
| `packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php` | Modify | Add tests for reorder endpoint and products count in view |

---

## Task 1: Add reorder-success lang key

**Files:**
- Modify: `packages/Webkul/Admin/src/Resources/lang/en/app.php`

- [ ] **Step 1: Add the lang key**

Find the `catalog.categories` section (around line 1597). It currently ends with:

```php
'create-success' => 'Category created successfully.',
'delete-category-root' => 'The Root category can not be deleted.',
'delete-failed' => 'Error encountered while deleting category',
'delete-success' => 'The category has been successfully deleted.',
'update-success' => 'Category updated successfully.',
```

Add `'reorder-success'` after `'update-success'`:

```php
'create-success' => 'Category created successfully.',
'delete-category-root' => 'The Root category can not be deleted.',
'delete-failed' => 'Error encountered while deleting category',
'delete-success' => 'The category has been successfully deleted.',
'reorder-success' => 'Categories reordered successfully.',
'update-success' => 'Category updated successfully.',
```

- [ ] **Step 2: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/lang/en/app.php
git commit -m "feat(categories): add reorder-success lang key"
```

---

## Task 2: Add reorder route

**Files:**
- Modify: `packages/Webkul/Admin/src/Routes/catalog-routes.php`

- [ ] **Step 1: Add the route**

Find the categories route group. It currently contains:

```php
Route::get('', 'index')->name('admin.catalog.categories.index');
Route::get('create', 'create')->name('admin.catalog.categories.create');
Route::post('create', 'store')->name('admin.catalog.categories.store');
Route::get('edit/{id}', 'edit')->name('admin.catalog.categories.edit');
Route::put('edit/{id}', 'update')->name('admin.catalog.categories.update');
Route::delete('edit/{id}', 'destroy')->name('admin.catalog.categories.delete');
Route::post('mass-delete', 'massDestroy')->name('admin.catalog.categories.mass_delete');
Route::post('mass-update', 'massUpdate')->name('admin.catalog.categories.mass_update');
Route::get('search', 'search')->name('admin.catalog.categories.search');
Route::get('tree', 'tree')->name('admin.catalog.categories.tree');
```

Add the reorder route after `mass-update`:

```php
Route::get('', 'index')->name('admin.catalog.categories.index');
Route::get('create', 'create')->name('admin.catalog.categories.create');
Route::post('create', 'store')->name('admin.catalog.categories.store');
Route::get('edit/{id}', 'edit')->name('admin.catalog.categories.edit');
Route::put('edit/{id}', 'update')->name('admin.catalog.categories.update');
Route::delete('edit/{id}', 'destroy')->name('admin.catalog.categories.delete');
Route::post('mass-delete', 'massDestroy')->name('admin.catalog.categories.mass_delete');
Route::post('mass-update', 'massUpdate')->name('admin.catalog.categories.mass_update');
Route::post('reorder', 'reorder')->name('admin.catalog.categories.reorder');
Route::get('search', 'search')->name('admin.catalog.categories.search');
Route::get('tree', 'tree')->name('admin.catalog.categories.tree');
```

- [ ] **Step 2: Commit**

```bash
git add packages/Webkul/Admin/src/Routes/catalog-routes.php
git commit -m "feat(categories): add reorder route"
```

---

## Task 3: Implement reorder endpoint (TDD)

**Files:**
- Test: `packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php`
- Modify: `packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php`

- [ ] **Step 1: Write failing tests**

Append to `packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php`:

```php
it('should reorder categories', function () {
    $this->loginAsAdmin();

    $cat1 = (new CategoryFaker)->factory()->create(['position' => 1]);
    $cat2 = (new CategoryFaker)->factory()->create(['position' => 2]);

    postJson(route('admin.catalog.categories.reorder'), [
        'positions' => [
            ['id' => $cat1->id, 'position' => 2],
            ['id' => $cat2->id, 'position' => 1],
        ],
    ])
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.reorder-success')]);

    expect($cat1->fresh()->position)->toBe(2)
        ->and($cat2->fresh()->position)->toBe(1);
});

it('should return 403 when reordering without edit permission', function () {
    $this->loginAsAdmin(['catalog.categories.edit' => false]);

    postJson(route('admin.catalog.categories.reorder'), [
        'positions' => [['id' => 1, 'position' => 1]],
    ])->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php --filter="reorder"
```

Expected: FAIL — `Route [admin.catalog.categories.reorder] not defined` or 404.

- [ ] **Step 3: Implement reorder() in CategoryController**

Add the following imports at the top of the file (after existing imports):

```php
use Illuminate\Support\Facades\DB;
```

Add the `reorder()` method after the existing `massUpdate()` method:

```php
/**
 * Reorder categories by updating their positions.
 */
public function reorder(\Illuminate\Http\Request $request): JsonResponse
{
    if (! bouncer()->hasPermission('catalog.categories.edit')) {
        return new JsonResponse(['message' => trans('admin::app.security.not-allowed')], 403);
    }

    foreach ($request->input('positions', []) as $item) {
        DB::table('categories')
            ->where('id', (int) $item['id'])
            ->update(['position' => (int) $item['position']]);
    }

    return new JsonResponse(['message' => trans('admin::app.catalog.categories.reorder-success')]);
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php --filter="reorder"
```

Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php \
        packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php
git commit -m "feat(categories): add reorder endpoint"
```

---

## Task 4: Pass product counts from index() (TDD)

**Files:**
- Test: `packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php`
- Modify: `packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php`

- [ ] **Step 1: Write failing test**

Append to `CategoryTest.php`:

```php
it('should pass product counts to category index view', function () {
    $this->loginAsAdmin();

    $category = (new CategoryFaker)->factory()->create();

    get(route('admin.catalog.categories.index'))
        ->assertOk()
        ->assertViewHas('productCounts');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php --filter="product counts"
```

Expected: FAIL — `Failed asserting that response view has [productCounts]`.

- [ ] **Step 3: Update index() to query and pass product counts**

In `CategoryController.php`, update the `index()` method from:

```php
public function index()
{
    if (request()->ajax()) {
        return datagrid(CategoryDataGrid::class)->process();
    }

    $categories = $this->categoryRepository->getCategoryTree();

    return view('admin::catalog.categories.index', compact('categories'));
}
```

To:

```php
public function index()
{
    if (request()->ajax()) {
        return datagrid(CategoryDataGrid::class)->process();
    }

    $categories = $this->categoryRepository->getCategoryTree();

    $productCounts = DB::table('product_categories')
        ->selectRaw('category_id, count(*) as cnt')
        ->groupBy('category_id')
        ->pluck('cnt', 'category_id');

    return view('admin::catalog.categories.index', compact('categories', 'productCounts'));
}
```

Also ensure `use Illuminate\Support\Facades\DB;` is present at the top of the file (added in Task 3 if not already there).

- [ ] **Step 4: Run test to verify it passes**

```bash
php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php --filter="product counts"
```

Expected: PASS.

- [ ] **Step 5: Run full category test suite**

```bash
php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php
```

Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php \
        packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php
git commit -m "feat(categories): pass product counts to index view"
```

---

## Task 5: Rewrite category index blade with products column and drag-and-drop

**Files:**
- Rewrite: `packages/Webkul/Admin/src/Resources/views/catalog/categories/index.blade.php`

This task replaces the entire `@pushOnce('scripts')` block and the `<v-category-tree>` element with a two-component structure.

- [ ] **Step 1: Rewrite index.blade.php**

Replace the entire file content with:

```blade
<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.categories.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.categories.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            {!! view_render_event('bagisto.admin.catalog.categories.index.create-button.before') !!}

            @if (bouncer()->hasPermission('catalog.categories.create'))
                <a href="{{ route('admin.catalog.categories.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.categories.index.add-btn')
                    </div>
                </a>
            @endif

            {!! view_render_event('bagisto.admin.catalog.categories.index.create-button.after') !!}
        </div>
    </div>

    {!! view_render_event('bagisto.admin.catalog.categories.list.before') !!}

    <v-category-tree
        :items='@json($categories)'
        :product-counts='@json($productCounts)'
        products-url-template="{{ route('admin.catalog.products.index') }}?filters[category_name][0]=CATEGORY_ID"
        reorder-url="{{ route('admin.catalog.categories.reorder') }}"
    />

    {!! view_render_event('bagisto.admin.catalog.categories.list.after') !!}

    @pushOnce('scripts')
        {{-- v-category-level: renders one sibling group inside a <draggable> and recurses --}}
        <script type="text/x-template" id="v-category-level-template">
            <draggable
                :list="nodes"
                item-key="id"
                handle=".icon-drag"
                :group="'cat-' + parentId"
                ghost-class="draggable-ghost"
                :animation="150"
                @end="tree.onReorder(nodes)"
            >
                <template #item="{ element: node }">
                    <div>
                        <div class="flex items-center gap-2.5 border-b px-4 py-3 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950">

                            {{-- Drag handle --}}
                            @if (bouncer()->hasPermission('catalog.categories.edit'))
                                <i class="icon-drag shrink-0 cursor-grab text-xl text-gray-400 transition-all dark:text-gray-500"></i>
                            @else
                                <span class="w-5 shrink-0"></span>
                            @endif

                            {{-- Name with indent, expand toggle, icon --}}
                            <div
                                class="flex flex-1 items-center gap-1.5 overflow-hidden"
                                :style="{ paddingLeft: ((level - 1) * 24) + 'px' }"
                            >
                                <i
                                    :class="[
                                        node.children && node.children.length
                                            ? (tree.isExpanded(node.id) ? 'icon-sort-down' : 'icon-sort-right')
                                            : 'invisible',
                                        'shrink-0 cursor-pointer rounded-md text-xl transition-all hover:bg-gray-100 dark:hover:bg-gray-950'
                                    ]"
                                    @click.stop="tree.toggle(node.id)"
                                ></i>

                                <img
                                    v-if="node.logo_url"
                                    :src="node.logo_url"
                                    :alt="node.name"
                                    class="h-8 w-8 shrink-0 rounded object-cover"
                                />

                                <i
                                    v-else
                                    :class="[
                                        node.children && node.children.length ? 'icon-folder' : 'icon-attribute',
                                        'shrink-0 text-2xl'
                                    ]"
                                ></i>

                                <a
                                    :href="tree.editUrl(node.id)"
                                    class="truncate hover:text-indigo-600 dark:hover:text-indigo-400"
                                    v-text="node.name"
                                ></a>
                            </div>

                            {{-- Products count --}}
                            <div class="w-24 text-center">
                                <a
                                    :href="tree.productsUrl(node.id)"
                                    class="text-sm text-indigo-600 hover:underline dark:text-indigo-400"
                                    v-text="tree.productsCount(node.id)"
                                ></a>
                            </div>

                            {{-- Position --}}
                            <div class="w-20 text-center text-sm text-gray-500 dark:text-gray-400">
                                @{{ node.position }}
                            </div>

                            {{-- Status badge --}}
                            <div class="w-28 text-center">
                                <span
                                    :class="node.status ? 'label-active' : 'label-canceled'"
                                    v-text="node.status
                                        ? '@lang('admin::app.catalog.categories.index.datagrid.active')'
                                        : '@lang('admin::app.catalog.categories.index.datagrid.inactive')'"
                                ></span>
                            </div>

                            {{-- Actions --}}
                            <div class="flex w-20 items-center justify-end gap-0.5">
                                @if (bouncer()->hasPermission('catalog.categories.edit'))
                                    <a :href="tree.editUrl(node.id)">
                                        <span class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"></span>
                                    </a>
                                @endif

                                @if (bouncer()->hasPermission('catalog.categories.delete'))
                                    <span
                                        class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                        @click.stop="tree.confirmDelete(node)"
                                    ></span>
                                @endif
                            </div>
                        </div>

                        {{-- Recursive children --}}
                        <v-category-level
                            v-if="tree.isExpanded(node.id) && node.children && node.children.length"
                            :nodes="node.children"
                            :parent-id="node.id"
                            :level="level + 1"
                        />
                    </div>
                </template>
            </draggable>
        </script>

        {{-- v-category-tree: root, column headers, provides state to children --}}
        <script type="text/x-template" id="v-category-tree-template">
            <div class="box-shadow mt-3 rounded-xl bg-white dark:bg-gray-900">
                {{-- Column headers --}}
                <div class="flex items-center gap-2.5 border-b px-4 py-3 text-xs font-semibold uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <div class="w-5 shrink-0"></div>
                    <div class="flex-1">@lang('admin::app.catalog.categories.index.datagrid.name')</div>
                    <div class="w-24 text-center">@lang('admin::app.catalog.categories.index.datagrid.no-of-products')</div>
                    <div class="w-20 text-center">@lang('admin::app.catalog.categories.index.datagrid.position')</div>
                    <div class="w-28 text-center">@lang('admin::app.catalog.categories.index.datagrid.status')</div>
                    <div class="w-20"></div>
                </div>

                <v-category-level
                    v-if="formattedItems.length"
                    :nodes="formattedItems"
                    :parent-id="0"
                    :level="1"
                />

                <div
                    v-if="formattedItems.length === 0"
                    class="py-12 text-center text-gray-400 dark:text-gray-600"
                >
                    @lang('admin::app.catalog.categories.index.title')
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-category-level', {
                template: '#v-category-level-template',

                inject: ['tree'],

                props: {
                    nodes: {
                        type: Array,
                        required: true,
                    },

                    parentId: {
                        type: [Number, String],
                        default: 0,
                    },

                    level: {
                        type: Number,
                        default: 1,
                    },
                },
            });

            app.component('v-category-tree', {
                template: '#v-category-tree-template',

                props: {
                    items: {
                        type: [Array, String],
                        default: () => ([]),
                    },

                    productCounts: {
                        type: Object,
                        default: () => ({}),
                    },

                    productsUrlTemplate: {
                        type: String,
                        default: '',
                    },

                    reorderUrl: {
                        type: String,
                        default: '',
                    },
                },

                provide() {
                    return {
                        tree: this,
                    };
                },

                data() {
                    return {
                        expanded: {},

                        formattedItems: typeof this.items === 'string'
                            ? JSON.parse(this.items)
                            : this.items,

                        editUrlTemplate: "{{ route('admin.catalog.categories.edit', 'CATEGORY_ID') }}",

                        deleteUrlTemplate: "{{ route('admin.catalog.categories.delete', 'CATEGORY_ID') }}",
                    };
                },

                methods: {
                    toggle(nodeId) {
                        const current = this.expanded[nodeId];

                        this.expanded[nodeId] = current === undefined ? false : ! current;
                    },

                    isExpanded(nodeId) {
                        return this.expanded[nodeId] !== false;
                    },

                    editUrl(id) {
                        return this.editUrlTemplate.replace('CATEGORY_ID', id);
                    },

                    productsUrl(id) {
                        return this.productsUrlTemplate.replace('CATEGORY_ID', id);
                    },

                    productsCount(id) {
                        return this.productCounts[id] ?? 0;
                    },

                    onReorder(nodes) {
                        const positions = nodes.map((node, index) => ({
                            id: node.id,
                            position: index + 1,
                        }));

                        this.$axios.post(this.reorderUrl, { positions })
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            })
                            .catch(() => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: "@lang('admin::app.catalog.categories.reorder-failed')",
                                });
                            });
                    },

                    confirmDelete(node) {
                        this.$emitter.emit('open-confirm-modal', {
                            agree: () => {
                                this.$axios.delete(this.deleteUrlTemplate.replace('CATEGORY_ID', node.id))
                                    .then(response => {
                                        this.removeNode(this.formattedItems, node.id);

                                        this.$emitter.emit('add-flash', {
                                            type: 'success',
                                            message: response.data.message,
                                        });
                                    })
                                    .catch(error => {
                                        this.$emitter.emit('add-flash', {
                                            type: 'error',
                                            message: error?.response?.data?.message ?? "@lang('admin::app.catalog.categories.delete-failed')",
                                        });
                                    });
                            },
                        });
                    },

                    removeNode(nodes, id) {
                        const idx = nodes.findIndex(n => n.id === id);

                        if (idx !== -1) {
                            nodes.splice(idx, 1);

                            return true;
                        }

                        for (const node of nodes) {
                            if (this.removeNode(node.children || [], id)) {
                                return true;
                            }
                        }

                        return false;
                    },
                },
            });
        </script>
    @endPushOnce

</x-admin::layouts>
```

> **Note:** The `onReorder` catch block references `'admin::app.catalog.categories.reorder-failed'` which doesn't exist. This string will render as the raw key in case of error — acceptable for now, or add the key to `app.php` alongside `reorder-success`.

- [ ] **Step 2: Run the full category test suite**

```bash
php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php
```

Expected: all tests pass.

- [ ] **Step 3: Build frontend assets**

```bash
cd packages/Webkul/Admin && npm run build
```

Or if using the root package.json:

```bash
npm run build
```

Expected: no errors.

- [ ] **Step 4: Verify in browser**

1. Open `/admin/catalog/categories` — confirm the "Number of Products" column header appears and each row shows a count linked to `/admin/catalog/products?filters[category_name][0]={id}`.
2. Drag a category row using the grip icon — confirm it reorders within its sibling group and a success flash appears.
3. Confirm expand/collapse still works by clicking the chevron icon.
4. Confirm delete still works via the delete icon.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Admin/src/Resources/views/catalog/categories/index.blade.php
git commit -m "feat(categories): add products count column and drag-and-drop sorting"
```

---

## Verification Summary

| Check | Command / Action |
|---|---|
| All category tests pass | `php artisan test --compact packages/Webkul/Admin/tests/Feature/Catalog/CategoryTest.php` |
| Products count shows in tree view | Open `/admin/catalog/categories` |
| Count link navigates to filtered products | Click a count number |
| Drag handle reorders siblings | Drag a row; verify flash + order persists on refresh |
| Cross-level drag is blocked | Attempt to drag root category into child group — should be prevented |
| Unauthorized reorder blocked | `postJson` to `/reorder` without edit permission → 403 |
