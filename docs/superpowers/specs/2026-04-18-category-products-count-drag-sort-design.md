# Category List: Products Count Column + Drag-and-Drop Sorting

**Date:** 2026-04-18  
**Branch:** `main`

---

## Context

The admin category index page (`/admin/catalog/categories`) uses a custom `v-category-tree` Vue component that renders a hierarchical tree view. It currently shows Name, Position, Status, and action buttons.

Two enhancements are needed:
1. A **products count** column showing how many products belong to each category, with a link to the product list filtered by that category.
2. **Drag-and-drop reordering** of categories within their sibling group (same parent level), persisted via a new API endpoint.

The DataGrid class (`CategoryDataGrid.php`) already has a `products_count` column but the DataGrid is not used for the index page — only the tree view is rendered.

---

## Feature 1: Products Count Column

### Backend

**`CategoryController::index()`** — add a single bulk query alongside the existing tree fetch:

```php
$productCounts = DB::table('product_categories')
    ->selectRaw('category_id, count(*) as cnt')
    ->groupBy('category_id')
    ->pluck('cnt', 'category_id');

return view('admin::catalog.categories.index', compact('categories', 'productCounts'));
```

No repository or model changes. One SQL query for all categories.

### Blade / Vue

Pass two new props to `v-category-tree`:

```html
<v-category-tree
    :items='@json($categories)'
    :product-counts='@json($productCounts)'
    products-url-template="{{ route('admin.catalog.products.index') }}?filters[category_name][0]=CATEGORY_ID"
/>
```

Add a **Products** column header between Name and Position. Each row renders:

```html
<a :href="productsUrl(item.node.id)">
    {{ productCounts[item.node.id] ?? 0 }}
</a>
```

Where `productsUrl(id)` replaces `CATEGORY_ID` in the URL template prop.

Column widths: Products → `w-24 text-center`

---

## Feature 2: Drag-and-Drop Sorting

### Backend

**New route** in `catalog-routes.php`:

```php
Route::post('categories/reorder', 'reorder')
    ->name('admin.catalog.categories.reorder');
```

**New controller method** `CategoryController::reorder()`:

```php
public function reorder(Request $request): JsonResponse
{
    if (! bouncer()->hasPermission('catalog.categories.edit')) {
        return new JsonResponse(['message' => trans('admin::app.security.not-allowed')], 403);
    }

    foreach ($request->input('positions', []) as $item) {
        DB::table('categories')
            ->where('id', $item['id'])
            ->update(['position' => $item['position']]);
    }

    return new JsonResponse(['message' => trans('admin::app.catalog.categories.reorder-success')]);
}
```

Accepts `positions` array: `[{id: int, position: int}, ...]`.  
Uses `DB::table` directly (same pattern as existing DataGrid queries). No cache invalidation needed — positions are dynamic.

**Lang key** to add in `lang/en/app.php` under `catalog.categories`:

```php
'reorder-success' => 'Categories reordered successfully',
```

### Frontend

**Template restructure** — replace the current flat `flatItems` rendering with a two-component recursive approach:

1. **`v-category-tree`** — root component, holds `formattedItems` state, methods (`toggle`, `isExpanded`, `confirmDelete`, `removeNode`, `onReorder`, `productsUrl`), and renders a `<v-category-level>` for the root level.

2. **`v-category-level`** — new inline component registered via `app.component('v-category-level', {...})`. Renders one level's children inside a `<draggable>` and recursively renders `<v-category-level>` for expanded children.

**Draggable configuration:**

```html
<draggable
    :list="nodes"
    item-key="id"
    handle=".drag-handle"
    :group="'cat-' + parentId"
    :animation="150"
    @end="$emit('reorder', nodes, parentId)"
>
```

- `handle=".drag-handle"` — drag only via grip icon (shows on row hover)
- `:group="'cat-' + parentId"` — prevents cross-level drops
- `@end` bubbles up to root via `$emit('reorder', nodes)`

**Drag handle icon** — prepended to each row (hidden by default, visible on `group-hover`):

```html
<span class="drag-handle icon-drag-horizontal shrink-0 cursor-grab opacity-0 text-xl group-hover:opacity-100 text-gray-400"></span>
```

**`onReorder(nodes)` in root component** — fires after drag, sends updated positions to API:

```js
onReorder(nodes) {
    const positions = nodes.map((node, index) => ({ id: node.id, position: index + 1 }));
    this.$axios.post(this.reorderUrl, { positions })
        .then(response => {
            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
        })
        .catch(() => {
            this.$emitter.emit('add-flash', { type: 'error', message: 'Reorder failed' });
        });
},
```

Optimistic update: `<draggable>` mutates `nodes` in-place, so the UI updates immediately on drop.

---

## Files Changed

| File | Change |
|---|---|
| `packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php` | Add `productCounts` query in `index()`, add `reorder()` method |
| `packages/Webkul/Admin/src/Routes/catalog-routes.php` | Add `POST categories/reorder` route |
| `packages/Webkul/Admin/src/Resources/views/catalog/categories/index.blade.php` | Add products count column, restructure to recursive `<draggable>` with `v-category-level` component, add drag handles |
| `packages/Webkul/Admin/src/Resources/lang/en/app.php` | Add `reorder-success` key under `catalog.categories` |

---

## Verification

1. Open `/admin/catalog/categories` — verify products count column appears for each category.
2. Click a count number — verify it opens the products list filtered by that category.
3. Drag a category row within its sibling group — verify it reorders and the new order persists on page refresh.
4. Verify dragging a root-level category does not mix with a child-level category.
5. Verify users without `catalog.categories.edit` permission cannot call the reorder endpoint (403).
