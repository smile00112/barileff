# Design: Category Product Count Column

**Date:** 2026-04-12
**Branch:** `main`
**Status:** Approved

---

## Summary

Add a "Products" count column to the admin categories list (`/admin/catalog/categories`). Each count is:

- Cached per category with a 1-hour TTL
- Rendered as a link that navigates to `/admin/catalog/products` pre-filtered to that category
- Invalidated via a queued job when products are created, updated, or deleted (fine-grained: only the affected category IDs are cleared)

---

## Scope

| Package | Files changed |
|---|---|
| `Admin` | `DataGrids/Catalog/CategoryDataGrid.php` |
| `Admin` | `Jobs/InvalidateCategoryProductCountCache.php` *(new)* |
| `Admin` | `Listeners/Category.php` *(new)* |
| `Admin` | `Providers/EventServiceProvider.php` |
| `Admin` | `Resources/lang/en/app.php` (translation key) |

No migration required — counts query the existing `product_categories` pivot table.

---

## 1. DataGrid Column

**File:** `packages/Webkul/Admin/src/DataGrids/Catalog/CategoryDataGrid.php`

Add one column at the end of `prepareColumns()`. The SQL query in `prepareQueryBuilder()` is unchanged.

```php
$this->addColumn([
    'index'    => 'products_count',
    'label'    => trans('admin::app.catalog.categories.index.datagrid.products-count'),
    'type'     => 'string',
    'sortable' => false,
    'closure'  => function ($row) {
        $count = \Illuminate\Support\Facades\Cache::remember(
            "cat_product_count_{$row->category_id}",
            3600,
            fn () => \Illuminate\Support\Facades\DB::table('product_categories')
                ->where('category_id', $row->category_id)
                ->count()
        );

        $url = route('admin.catalog.products.index')
             . '?filters[category_name][0]=' . $row->category_id;

        return '<a href="' . $url . '">' . $count . '</a>';
    },
]);
```

**Scope:** counts ALL products in the category (no status filter), direct assignments only (no subcategory roll-up).

**Translation key** added to `packages/Webkul/Admin/src/Resources/lang/en/app.php`:

```
catalog.categories.index.datagrid.products-count => 'Products'
```

---

## 2. Cache Invalidation Job

**File:** `packages/Webkul/Admin/src/Jobs/InvalidateCategoryProductCountCache.php`

```
Implements: ShouldQueue
Traits: Dispatchable, InteractsWithQueue, Queueable, SerializesModels
```

Constructor accepts `array $categoryIds`.

`handle()` iterates `$categoryIds` and calls `Cache::forget("cat_product_count_{$id}")` for each.

No additional dependencies. Runs on the default queue.

---

## 3. Listener

**File:** `packages/Webkul/Admin/src/Listeners/Category.php`

Four methods, all dispatch `InvalidateCategoryProductCountCache`:

| Method | Event | Category IDs source |
|---|---|---|
| `afterProductCreated($product)` | `catalog.product.create.after` | `$product->categories()->pluck('id')->all()` |
| `beforeProductUpdated($product)` | `catalog.product.update.before` | Snapshot into `static $oldIds[$product->id]` |
| `afterProductUpdated($product)` | `catalog.product.update.after` | `static $oldIds` ∪ current categories |
| `beforeProductDeleted($product)` | `catalog.product.delete.before` | `$product->categories()->pluck('id')->all()` |

The `update.before` / `update.after` pair is needed because the pivot table is already rewritten by the time `update.after` fires, so old category IDs must be captured before.

The `delete.before` event fires before cascades, so pivot rows are still readable.

If `$categoryIds` is empty (product had no category), the job is not dispatched.

---

## 4. EventServiceProvider Wiring

**File:** `packages/Webkul/Admin/src/Providers/EventServiceProvider.php`

Add to `$listen`:

```php
'catalog.product.create.after'  => [[Category::class, 'afterProductCreated']],
'catalog.product.update.before' => [[Category::class, 'beforeProductUpdated']],
'catalog.product.update.after'  => [[Category::class, 'afterProductUpdated']],
'catalog.product.delete.before' => [[Category::class, 'beforeProductDeleted']],
```

---

## Cache Key Scheme

| Key | Type | TTL |
|---|---|---|
| `cat_product_count_{category_id}` | integer | 3600 s (1 hour) |

Uses the application's default cache store (configured via `CACHE_DRIVER`). No cache tags used (to remain compatible with non-taggable drivers like `file`).

---

## What Is Deliberately Out of Scope

- Subcategory product roll-up (count is direct assignments only)
- Filtering by product status/visibility
- Sorting the DataGrid by product count (would require a SQL subquery or separate join)
- Cache warming on deploy (first request per category warms the cache naturally)
