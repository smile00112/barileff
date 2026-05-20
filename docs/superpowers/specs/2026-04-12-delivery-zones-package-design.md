# Design: DeliveryZones Package

**Date:** 2026-04-12
**Branch:** `main`
**Status:** Approved

---

## Summary

The `DeliveryZones` package implements zone-based delivery for the storefront. The admin defines **cities** with geographic boundaries (Yandex Maps polygons) and **zones** within those cities, each zone bound to an inventory source (warehouse) and carrying a set of order-total-based rates. When a customer pins a delivery point on the map, the system resolves which zone contains that point, selects the warehouse, and calculates the shipping cost through the standard Bagisto `Shipping` carrier pipeline.

---

## Scope

| Package | Role |
|---|---|
| `DeliveryZones` | Domain models, services, admin CRUD, shop API |
| `Shipping` | Migrations, `DeliveryZones` carrier class |
| `Checkout` | Cart model columns (`delivery_zone_id`, `delivery_point_lat/lng`, `delivery_zone_mode`) |
| `Admin` | Admin routes, menu, ACL, lang, DataGrids, views |
| `Shop` | Shop routes, `CartResource` extension, checkout views |

---

## 1. Domain Model

### Entities

```
DeliveryCity
  id, code, name, country, state, center_lat, center_lng, polygon_json, is_active
  has_many: DeliveryZone

DeliveryZone
  id, city_id, code, name, polygon_json
  polygon_color, polygon_fill_opacity, polygon_stroke_opacity
  delivery_time_minutes, is_active
  belongs_to: DeliveryCity
  has_many: DeliveryZoneRate
  belongs_to_many: InventorySource (via delivery_zone_inventory_sources)

DeliveryZoneRate
  id, zone_id, min_order_total, price, sort_order
  belongs_to: DeliveryZone
```

### Constraints

- `delivery_zones.code` is unique within a city (`UNIQUE(city_id, code)`)
- `delivery_zone_rates.min_order_total` is unique within a zone (`UNIQUE(zone_id, min_order_total)`)
- A zone has exactly one inventory source (the pivot is many-to-many structurally, but the UI enforces one)
- Cascade delete: city → zones → rates & pivot rows

---

## 2. Database Schema

### `delivery_cities`

| Column | Type | Notes |
|---|---|---|
| `id` | uint PK | |
| `code` | string UNIQUE | Short identifier |
| `name` | string | City name, compared case-insensitively |
| `country` | string | Country code |
| `state` | string nullable | Region/state |
| `center_lat` | decimal(10,7) nullable | Map initial center (added in second migration) |
| `center_lng` | decimal(10,7) nullable | |
| `polygon_json` | json nullable | `[[lat,lng],...]`, city boundary polygon (added in second migration) |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

### `delivery_zones`

| Column | Type | Notes |
|---|---|---|
| `id` | uint PK | |
| `city_id` | uint FK→delivery_cities | cascade delete |
| `code` | string | Unique per city |
| `name` | string | Displayed as shipping method title |
| `polygon_json` | json | Zone boundary `[[lat,lng],...]` |
| `polygon_color` | string | Hex color, default `#0077cc` |
| `polygon_fill_opacity` | decimal | 0–1, default 0.2 |
| `polygon_stroke_opacity` | decimal | 0–1, default 1 |
| `delivery_time_minutes` | uint nullable | Informational ETA |
| `is_active` | boolean | default true |
| `created_at`, `updated_at` | timestamps | |

### `delivery_zone_rates`

| Column | Type | Notes |
|---|---|---|
| `id` | uint PK | |
| `zone_id` | uint FK→delivery_zones | cascade delete |
| `min_order_total` | decimal(12,4) | Threshold; unique per zone |
| `price` | decimal(12,4) | Shipping cost |
| `sort_order` | uint | Tie-break ordering |
| `created_at`, `updated_at` | timestamps | |

### `delivery_zone_inventory_sources`

| Column | Type | Notes |
|---|---|---|
| `zone_id` | uint FK→delivery_zones | composite PK |
| `inventory_source_id` | uint FK→inventory_sources | composite PK |

### Cart columns (in `Checkout` package migration)

| Column | Type | Notes |
|---|---|---|
| `delivery_zone_id` | uint nullable | FK→delivery_zones |
| `delivery_point_lat` | decimal nullable | Customer pin latitude |
| `delivery_point_lng` | decimal nullable | Customer pin longitude |
| `delivery_zone_mode` | string nullable | `'manual'` or `'auto'` |

---

## 3. Service Layer

### `ZoneSelector`

Resolves the `DeliveryZone` for a cart (or for ad-hoc selection without a cart).

**`resolveZone(CartContract $cart): ?DeliveryZone`**

1. Loads `channel.inventory_sources`, `shipping_address`, `delivery_zone` onto the cart.
2. Gets the channel's `$sourceIds`.
3. If `delivery_zone_mode === 'manual'` and `delivery_zone_id` is set: looks up the zone by ID, verifies it is active and belongs to a channel source. Returns it if found; falls through otherwise.
4. Auto mode: requires `delivery_point_lat/lng` non-null and `shipping_address.city` non-empty.
5. Loads all active zones for that city (case-insensitive match) that have a channel inventory source.
6. Iterates zones; returns the first one whose `polygon_json` contains the delivery point via ray-casting.

**`resolveZoneBySelection(...): ?DeliveryZone`**

Same logic but takes explicit parameters instead of a cart. Used by the Shop API before a cart exists.

**`pointInPolygon(float $lat, float $lng, array $polygon): bool`**

Ray-casting (Jordan curve theorem). Returns `false` for polygons with fewer than 3 vertices.

---

### `DeliveryZoneRateResolver`

**`resolveRate(CartContract $cart): ?DeliveryZoneRate`**

Calls `ZoneSelector::resolveZone()`, then delegates to `resolveRateForZone()`.

**`resolveRateForZone(CartContract $cart, DeliveryZone $zone): ?DeliveryZoneRate`**

Selects the rate where `min_order_total ≤ cart.sub_total`, ordered by `min_order_total DESC, sort_order DESC`. Returns the first match (highest applicable threshold wins).

---

### `CartDeliveryZoneManager`

**`applySelection(CartContract $cart, ?float $lat, ?float $lng, ?int $zoneId): void`**

1. Saves `delivery_point_lat`, `delivery_point_lng`, `delivery_zone_id` and computed `delivery_zone_mode` to the cart.
2. Calls `ZoneSelector::resolveZone()`.
3. If a zone is found: sets `cart.inventory_source_id` to the zone's first inventory source and stores it in the session (`selected_inventory_source_id`).
4. If no zone: clears `inventory_source_id` and removes the session key.

**`resolveMode(?float $lat, ?float $lng, ?int $zoneId): ?string`**

- `'manual'` if `$zoneId` is set
- `'auto'` if only coordinates are set
- `null` otherwise

---

## 4. Shipping Carrier

**File:** `packages/Webkul/Shipping/src/Carriers/DeliveryZones.php`

Registered as carrier `delivery_zones`, method key `delivery_zones_delivery_zones`.

`calculate()` flow:
1. `isAvailable()` — standard carrier check (enabled in system config).
2. Gets current cart.
3. `ZoneSelector::resolveZone($cart)` — zone must be resolved; logs a warning if not.
4. `DeliveryZoneRateResolver::resolveRateForZone($cart, $zone)` — rate must exist.
5. Constructs a `CartShippingRate` with:
   - `carrier_title` from system config
   - `method_title` = zone name
   - `price` = `core()->convertPrice($rate->price)` (channel currency)
   - `base_price` = raw `$rate->price`

---

## 5. Listener

**`RefreshDeliveryZoneRatesOnCartChange`**

Listens to `checkout.cart.collect.totals.before.shipping`.

Recalculates delivery rates when cart items change (needed because rates are order-total-dependent). Skips if:
- Shipping method is not `delivery_zones_delivery_zones`
- Cart has no stockable items
- Cart has no shipping address

Calls `Shipping::collectRates()` then `Cart::refreshCart()`.

---

## 6. Admin Panel

### Routes (registered in `Admin` package)

| Route name | Method | URI | Action |
|---|---|---|---|
| `admin.settings.delivery_cities.index` | GET | `/admin/settings/delivery-cities` | list + DataGrid |
| `admin.settings.delivery_cities.create` | GET | `/admin/settings/delivery-cities/create` | create form |
| `admin.settings.delivery_cities.store` | POST | `/admin/settings/delivery-cities` | store |
| `admin.settings.delivery_cities.edit` | GET | `/admin/settings/delivery-cities/{id}/edit` | edit form |
| `admin.settings.delivery_cities.update` | PUT | `/admin/settings/delivery-cities/{id}` | update |
| `admin.settings.delivery_cities.delete` | DELETE | `/admin/settings/delivery-cities/{id}` | delete |
| `admin.settings.delivery_cities.zones` | GET | `/admin/settings/delivery-cities/{id}/zones` | zones dashboard for city |
| `admin.settings.delivery_zones.index` | GET | `/admin/settings/delivery-zones` | list + DataGrid |
| `admin.settings.delivery_zones.create` | GET | `/admin/settings/delivery-zones/create` | create form |
| `admin.settings.delivery_zones.store` | POST | `/admin/settings/delivery-zones` | store |
| `admin.settings.delivery_zones.edit` | GET | `/admin/settings/delivery-zones/{id}/edit` | edit form |
| `admin.settings.delivery_zones.update` | PUT | `/admin/settings/delivery-zones/{id}` | update |
| `admin.settings.delivery_zones.delete` | DELETE | `/admin/settings/delivery-zones/{id}` | delete |

### DataGrids

**`DeliveryCitiesDataGrid`** — columns: id, code, name, country, state, is_active. Actions: manage-zones (view icon), edit, delete.

**`DeliveryZonesDataGrid`** — columns: id, code, name, city_name (join), delivery_time_minutes, is_active. Actions: edit, delete.

### Views (`delivery-zones::settings.*`)

| View | Purpose |
|---|---|
| `delivery-cities/index` | City list with DataGrid |
| `delivery-cities/create` | New city form + Yandex Maps polygon editor (`#city-map`) |
| `delivery-cities/edit` | Edit city + polygon + Set Center tool |
| `delivery-cities/zones` | City detail page: map overlay of all zones + inline zone management |
| `delivery-zones/index` | Zone list with DataGrid |
| `delivery-zones/create` | New zone form + polygon editor + rates table |
| `delivery-zones/edit` | Edit zone + polygon editor + rates table |

### Polygon Editor (Yandex Maps)

- Script: `https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey=...`
- Polygon format: `[[lat, lng], ...]` — closing vertex repeated in the first position is stripped before save.
- Container IDs: `#zone-map` (zone views), `#city-map` (city views).
- `polygon_json` is submitted as a JSON string in the hidden form field; the controller `json_decode`s it before storing.

### Form Validation (`DeliveryZoneRequest`)

Required fields: `city_id`, `code` (alpha_dash, unique per city), `name`, `polygon_json` (valid JSON), `polygon_color` (hex regex), `polygon_fill_opacity` (0–1), `polygon_stroke_opacity` (0–1), `inventory_source_ids` (integer, exists), `rates` (array min:1).

Rate rows: `min_order_total` (numeric ≥ 0), `price` (numeric ≥ 0), `sort_order` (nullable integer).

### Zone Update Strategy

On `update`, the controller **deletes all existing rates** and re-inserts them from the form payload. This is a full replace, not a diff. Inventory sources are synced (`sync([$id])`).

---

## 7. Shop API

**Base prefix:** `/api/delivery-zones` (Shop REST routes)

### `GET /api/delivery-zones`

Returns cities with their active zones for the current channel. Zones are filtered by channel inventory sources.

Response shape:
```json
{
  "data": {
    "data": [
      {
        "id": 1,
        "name": "Москва",
        "center_lat": 55.7558,
        "center_lng": 37.6173,
        "polygon_json": [[55.7, 37.5], ...],
        "zones": [
          {
            "id": 1,
            "name": "Центр",
            "polygon_json": [...],
            "polygon_color": "#0077cc",
            "polygon_fill_opacity": 0.2,
            "polygon_stroke_opacity": 1.0,
            "delivery_time_minutes": 60,
            "inventory_source_id": 1,
            "rates": [
              {"min_order_total": 2000, "price": 0, "sort_order": 2},
              {"min_order_total": 0, "price": 300, "sort_order": 1}
            ]
          }
        ]
      }
    ]
  }
}
```

Rates are sorted `min_order_total DESC, sort_order DESC` (highest threshold first).

### `POST /api/delivery-zones/select`

Selects a delivery zone. Accepts:

| Field | Type | Notes |
|---|---|---|
| `delivery_zone_id` | int nullable | Manual zone pick by ID |
| `delivery_point_lat` | float nullable | −90..90 |
| `delivery_point_lng` | float nullable | −180..180 |
| `city` | string nullable | City name for auto-resolution |
| `shipping_method` | string nullable | Must be `delivery_zones_delivery_zones` |

Behavior:
1. `ZoneSelector::resolveZoneBySelection()` — tries manual ID first, then coordinate + city polygon match.
2. If zone not found: returns HTTP 422 with `{zone: null, inventory_source_id: null, message: ...}`.
3. If zone found + no cart: returns `{zone: {id, name}, inventory_source_id}`.
4. If zone found + cart exists:
   - Sets shipping address city if `city` provided.
   - `CartDeliveryZoneManager::applySelection()` — saves zone/coords to cart, sets warehouse.
   - `Cart::collectTotals()`.
   - Saves shipping method if `delivery_zones_delivery_zones`.
   - Collects shipping rates.
   - Returns `{zone, inventory_source_id, cart: CartResource, shipping_methods: [...]}`.

### `GET /api/delivery-zones/pickup-points`

Returns active inventory sources of the current channel that have `latitude` and `longitude` set. Used to display pickup point markers on the map.

---

## 8. Cart Integration

The `Cart` model has four additional columns contributed by the `Checkout` package migration:

| Column | Purpose |
|---|---|
| `delivery_zone_id` | FK to the selected zone |
| `delivery_point_lat` | Delivery pin latitude |
| `delivery_point_lng` | Delivery pin longitude |
| `delivery_zone_mode` | `'manual'` (explicit zone pick) or `'auto'` (coordinate match) |

`CartResource` exposes a `delivery_zone` object when a zone is resolved:
```json
{
  "delivery_zone": {
    "id": 1,
    "name": "Центр",
    "mode": "manual",
    "delivery_time_minutes": 60
  }
}
```

---

## 9. Zone Resolution Flow

```
Customer pins a point (lat/lng) or picks a zone on the map
  ↓
POST /api/delivery-zones/select {delivery_point_lat, delivery_point_lng, city}
  ↓
ZoneSelector::resolveZoneBySelection()
  ├─ manual: lookup by delivery_zone_id (verify active + channel source)
  └─ auto: filter zones by city name → iterate → pointInPolygon()
  ↓
Zone found?
  ├─ No → HTTP 422, clear inventory_source_id in cart/session
  └─ Yes → set session['selected_inventory_source_id']
           CartDeliveryZoneManager::applySelection() → cart updated
           Shipping::collectRates() → DeliveryZones carrier → rate resolved
           CartResource returned with delivery_zone, grand_total, shipping_methods
```

---

## 10. Rate Resolution Flow

```
Cart total changes (item add/remove/update)
  ↓
Event: checkout.cart.collect.totals.before.shipping
  ↓
RefreshDeliveryZoneRatesOnCartChange::handle()
  ↓ (skip if not delivery_zones method, no stockable items, no shipping address)
Shipping::collectRates()
  ↓
DeliveryZones carrier::calculate()
  ↓
ZoneSelector::resolveZone($cart)
  ↓ zone found
DeliveryZoneRateResolver::resolveRateForZone($cart, $zone)
  SELECT rate WHERE min_order_total <= cart.sub_total
  ORDER BY min_order_total DESC, sort_order DESC
  LIMIT 1
  ↓
CartShippingRate { price, method_title = zone.name }
```

---

## 11. ACL and Menu

Registered in `Admin` package:

| ACL resource | Key |
|---|---|
| Delivery Cities | `admin.settings.delivery_cities` (index/create/edit/delete) |
| Delivery Zones | `admin.settings.delivery_zones` (index/create/edit/delete) |

Menu entries under **Settings** sidebar section.

---

## 12. Translations

Namespace: `admin::app.settings.delivery_zones.*`

Key groups:

| Group | Purpose |
|---|---|
| `edit.*` | Zone create/edit form labels |
| `zones.*` | DataGrid columns and actions |
| `response.*` | Flash messages (city-created, city-updated, city-deleted, zone-created, zone-updated, zone-deleted) |
| `datagrid.cities.*` | Cities DataGrid column headers |
| `datagrid.zones.*` | Zones DataGrid column headers |

Shop translations: `shop::app.delivery-zones.zone-not-found`

---

## 13. Tests

| File | Scope |
|---|---|
| `packages/Webkul/Admin/tests/Feature/Settings/DeliveryZonesTest.php` | Admin CRUD for cities and zones |
| `packages/Webkul/Shop/tests/Feature/Checkout/DeliveryZonesCheckoutTest.php` | Zone selection flow, cart integration |
| `packages/Webkul/Shop/tests/Feature/API/DeliveryZonesApiTest.php` | Shop API endpoints |

---

## 14. What Is Deliberately Out of Scope

- Multi-warehouse per zone (current: exactly one `inventory_source` per zone)
- Zone hierarchy / nested zones
- Time-window-based rates (only order-total-based)
- Customer-facing zone name translations (single-language)
- Caching of polygon resolution (resolved live on each request)
