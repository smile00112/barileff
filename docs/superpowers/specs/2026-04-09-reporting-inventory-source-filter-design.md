# Reporting: Inventory Source Filter

**Date:** 2026-04-09
**Branch:** add_product_import_add_fcm

---

## Context

The admin reporting pages (`/admin/reporting/sales` and `/admin/reporting/customers`) currently support filtering by **channel** and **date range**. There is no way to drill down by warehouse/inventory source.

Orders are linked to inventory sources **indirectly through shipments** (`shipments.inventory_source_id`). An order is considered "from" a source if at least one of its shipments came from that source.

The goal is to add an inventory source dropdown filter to both report pages so admins can analyse sales and customer behaviour per warehouse.

---

## Decisions Made

| Question | Decision |
|---|---|
| Linkage mechanism | Via `shipments.inventory_source_id` (order has shipment from source) |
| Widgets without shipment data | Unchanged — show data without source filter |
| Multiple shipments on one order | Include order if **at least one** shipment is from selected source |
| Dropdown scope | All active inventory sources (`status = 1`), not filtered by channel |
| SQL approach | `whereIn(orders.id, Shipment::where('inventory_source_id')->select('order_id'))` |

---

## Architecture

### Data Flow

```
Blade (list of active sources)
  → Vue v-reporting-filters (new <select>)
  → GET /admin/reporting/sales/stats?inventory_source=2&channel=...&start=...&end=...
  → Reporting/Controller.php
  → AbstractReporting::setInventorySource(2)
  → Sale / Customer helpers
  → whereIn filter on relevant order queries
  → JSON response to Vue widget
```

### Files to Modify

| Layer | File | Change |
|---|---|---|
| Controller | `packages/Webkul/Admin/src/Http/Controllers/Reporting/SaleController.php` | Pass `$inventorySources` to view |
| Controller | `packages/Webkul/Admin/src/Http/Controllers/Reporting/CustomerController.php` | Pass `$inventorySources` to view |
| Controller | `packages/Webkul/Admin/src/Http/Controllers/Reporting/Controller.php` | Read `inventory_source` from request, call `setInventorySource()` |
| Helper base | `packages/Webkul/Admin/src/Helpers/Reporting/AbstractReporting.php` | Add `$inventorySourceId` property + `setInventorySource()` |
| Helper orchestrator | `packages/Webkul/Admin/src/Helpers/Reporting.php` | Forward `inventory_source` to sub-helpers |
| Helper | `packages/Webkul/Admin/src/Helpers/Reporting/Sale.php` | Apply `whereIn` filter in 7 methods |
| Helper | `packages/Webkul/Admin/src/Helpers/Reporting/Customer.php` | Apply `whereIn` filter in 3 methods |
| Blade | `packages/Webkul/Admin/src/Resources/views/reporting/sales/index.blade.php` | Pass `$inventorySources` to `v-reporting-filters` |
| Blade | `packages/Webkul/Admin/src/Resources/views/reporting/customers/index.blade.php` | Pass `$inventorySources` to `v-reporting-filters` |
| Blade | `packages/Webkul/Admin/src/Resources/views/reporting/view.blade.php` | Add source filter to `v-reporting-stats-table` |
| Vue | `v-reporting-filters` component (embedded in Blade) | New `<select>` for inventory source |

---

## Backend Implementation

### AbstractReporting.php

Add property and fluent setter:

```php
protected ?int $inventorySourceId = null;

public function setInventorySource(?int $id): static
{
    $this->inventorySourceId = $id;
    return $this;
}
```

### Reporting.php (orchestrator)

When building sub-helpers (Sale, Customer), chain:
```php
->setInventorySource($this->inventorySourceId)
```
alongside the existing `->setChannel()`, `->setStartDate()`, `->setEndDate()`.

### Controller.php (base Reporting controller)

In `stats()`, `viewStats()`, and `export()` methods, after existing filter setup:
```php
->setInventorySource($request->integer('inventory_source') ?: null)
```

### Sale.php — methods with whereIn filter

Apply to queries that aggregate `orders`:

- `getTotalSales()` / `getTotalSalesOverTime()`
- `getAverageSales()`
- `getTotalOrders()` / `getTotalOrdersOverTime()`
- `getTaxCollected()`
- `getShippingCollected()`
- `getTopPaymentMethods()`
- `getRefunds()` / `getRefundsOverTime()` (via `refunds.order_id`)

**Not applied to:** `getAbandonedCarts()`, `getPurchaseFunnel()` (operate on `carts`, not `orders`)

Pattern (add via `->when()`):
```php
->when($this->inventorySourceId, fn ($q) => $q->whereIn('id',
    \Webkul\Sales\Models\Shipment::where('inventory_source_id', $this->inventorySourceId)
        ->select('order_id')
))
```

### Customer.php — methods with whereIn filter

Apply to queries that join through `orders`:

- `getCustomersWithMostSales()`
- `getCustomersWithMostOrders()`
- `getTopCustomerGroups()`

**Not applied to:** `getTotalCustomers()`, `getCustomersTrafficStats()`, `getCustomersWithMostReviews()`

Pattern — filter on the orders subquery or join:
```php
->when($this->inventorySourceId, fn ($q) => $q->whereHas('orders', fn ($o) => $o->whereIn('id',
    \Webkul\Sales\Models\Shipment::where('inventory_source_id', $this->inventorySourceId)
        ->select('order_id')
)))
```

---

## Frontend Implementation

### SaleController / CustomerController index()

Inject `InventorySourceRepository` and pass to view:
```php
public function __construct(
    protected InventorySourceRepository $inventorySourceRepository,
) {}

public function index(): View
{
    return view('admin::reporting.sales.index', [
        'inventorySources' => $this->inventorySourceRepository->findWhere(['status' => 1]),
        // existing vars...
    ]);
}
```

### Blade — sales/index.blade.php & customers/index.blade.php

Pass list to Vue filter component:
```blade
<v-reporting-filters
    :inventory-sources="{{ json_encode($inventorySources) }}"
    ...
>
```

### Vue v-reporting-filters

Add prop `inventorySources`, add `inventory_source` to reactive `filters` object, add select:
```html
<select v-model="filters.inventory_source" @change="$emit('reporting-filter-updated', filters)">
    <option value="">{{ $t('reporting.all-sources') }}</option>
    <option v-for="source in inventorySources" :key="source.id" :value="source.id">
        {{ source.name }}
    </option>
</select>
```

`inventory_source` is included automatically in all `GET` requests to stats endpoints.

### view.blade.php (detailed view)

Add same `inventorySources` data + `inventory_source` filter to `v-reporting-stats-table` component, which forwards it as a query param to the view stats API.

---

## Verification

1. Go to `/admin/reporting/sales` — confirm new inventory source dropdown appears next to channel
2. Select a specific source — confirm all order-based widgets update (total sales, orders, etc.)
3. Confirm cart-based widgets (abandoned carts, purchase funnel) do NOT change when source is selected
4. Repeat on `/admin/reporting/customers` — most-sales and most-orders widgets filter correctly
5. Confirm customer traffic / total customers widgets are unaffected
6. Select source + channel together — confirm both filters apply simultaneously
7. Click into a detailed view (e.g. total-sales table) — confirm source filter persists on view page
8. Export CSV/XLS from detailed view — confirm export respects the source filter
