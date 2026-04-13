# Delivery Zones — GeoJSON Import / Export

**Date:** 2026-04-13
**Branch:** `add_product_import_add_fcm`
**Package:** `Webkul\DeliveryZones`

---

## Goal

Add GeoJSON import and export to the delivery zones admin panel so operators can bulk-load polygon data from an external zone editor and round-trip it back out.

---

## GeoJSON Format

Standard `FeatureCollection` where each `Feature` carries one delivery zone polygon.

Key conventions:

| GeoJSON field | Maps to |
|---|---|
| `properties.description` | `#cid=<city_code>` — city identifier via `DeliveryCity.code` |
| `properties.fill` | `delivery_zones.polygon_color` |
| `properties.fill-opacity` | `delivery_zones.polygon_fill_opacity` |
| `properties.stroke` | same as fill (ignored on import; derived from `polygon_color` on export) |
| `properties.stroke-width` | always `"1"` (constant) |
| `properties.stroke-opacity` | `delivery_zones.polygon_stroke_opacity` |
| `geometry.coordinates[0]` | `delivery_zones.polygon_json` (outer ring, `[[lng,lat],…]`) |
| feature `id` | sequential index (export only) |

---

## Database Changes

### Migration — `make_city_id_nullable_and_recode_unique_on_delivery_zones`

```
delivery_zones.city_id:  unsignedInteger NOT NULL  →  unsignedInteger NULLABLE
unique(city_id, code)   →  dropped
unique(code)            →  added (globally unique zone codes)
```

**Why:** zones imported without a matching city must be storable; city-scoped uniqueness is no longer needed once `code` is globally unique.

### Affected validation (`DeliveryZoneRequest`)

- `city_id`: `required|exists` → `nullable|exists:delivery_cities,id`
- `code` uniqueness: `unique:delivery_zones,code,{id},id,city_id,{city_id}` → `unique:delivery_zones,code,{id}`

---

## Import

### Routes

```
GET  /delivery-zones/import   → DeliveryZoneController@importForm
POST /delivery-zones/import   → DeliveryZoneController@import
```

### Import Form (`importForm`)

Fields:

| Field | Type | Required | Notes |
|---|---|---|---|
| `file` | JSON file upload | yes | Must be valid JSON FeatureCollection |
| `default_city_id` | select (active cities) | no | Fallback city when `#cid=` code not found in DB |
| `inventory_source_id` | select (active sources) | yes | Applied to all imported zones |
| `default_rate[min_order_total]` | number | yes | Default 0 |
| `default_rate[price]` | number | yes | Delivery price applied to all imported zones |

### Import Action (`import`)

Validation:

- `file`: required, file, mimes:json
- `inventory_source_id`: required, exists:inventory_sources,id
- `default_city_id`: nullable, exists:delivery_cities,id
- `default_rate.min_order_total`: required, numeric, min:0
- `default_rate.price`: required, numeric, min:0

Processing (single DB transaction):

1. Decode file JSON; reject if not a valid FeatureCollection or `features` is empty.
2. For each feature:
   a. Parse code: strip `#cid=` prefix from `properties.description`. This value becomes both the zone `code` and `name`.
   b. Resolve city: find `DeliveryCity` where `code = <parsed_code>`; if not found, use `default_city_id` (may be null).
   c. Read colors: `fill` → `polygon_color`, `fill-opacity` → `polygon_fill_opacity`, `stroke-opacity` → `polygon_stroke_opacity`.
   d. Read polygon: `geometry.coordinates[0]` → `polygon_json`.
   e. Create `DeliveryZone` with `is_active = true`.
   f. Sync `inventory_sources` with the selected `inventory_source_id`.
   g. Create one `DeliveryZoneRate` from `default_rate`.
3. Flash success: "N zones imported." Redirect to zones index.
4. On any error: rollback transaction, flash error message.

---

## Export

### Route

```
GET /delivery-zones/export   → DeliveryZoneController@export
```

### Export Action (`export`)

1. Load all `DeliveryZone` records with `city` eager-loaded (no filtering — exports all zones).
2. Build FeatureCollection:

```json
{
  "type": "FeatureCollection",
  "metadata": {
    "name": "Delivery Zones",
    "creator": "Admin App Zone Editor"
  },
  "features": [...]
}
```

Per zone (index `$i`):

```json
{
  "type": "Feature",
  "id": $i,
  "geometry": {
    "type": "Polygon",
    "coordinates": [ zone.polygon_json ]
  },
  "properties": {
    "description": "#cid={city.code}",
    "fill": zone.polygon_color,
    "fill-opacity": zone.polygon_fill_opacity,
    "stroke": zone.polygon_color,
    "stroke-width": "1",
    "stroke-opacity": zone.polygon_stroke_opacity
  }
}
```

- If zone has no city: `description = ""`
- Response headers: `Content-Type: application/json`, `Content-Disposition: attachment; filename="delivery-zones.json"`

---

## UI Changes

### Zones Index (`delivery-zones::settings.delivery-zones.index`)

Add two buttons to the header row next to "Add zone":

- **Export** — plain anchor link to `route('admin.settings.delivery_zones.export')`, `download` attribute
- **Import** — link to `route('admin.settings.delivery_zones.import')`

### Import View (`delivery-zones::settings.delivery-zones.import`)

Standard Bagisto admin form layout:
- File picker for JSON
- City dropdown (optional, labelled "Default city (fallback)")
- Inventory source dropdown (required)
- Two number inputs for default rate (min order total + price)
- Save / Back buttons

---

## Files to Create / Modify

| File | Action |
|---|---|
| `Webkul/Shipping/src/Database/Migrations/YYYY_MM_DD_make_city_id_nullable_on_delivery_zones.php` | Create |
| `Webkul/DeliveryZones/src/Http/Controllers/Admin/DeliveryZoneController.php` | Modify (add `importForm`, `import`, `export`) |
| `Webkul/DeliveryZones/src/Http/Requests/DeliveryZoneRequest.php` | Modify (relax city_id + code uniqueness) |
| `Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/import.blade.php` | Create |
| `Webkul/DeliveryZones/src/Resources/views/settings/delivery-zones/index.blade.php` | Modify (add Import/Export buttons) |
| `Webkul/Admin/src/Routes/settings-routes.php` | Modify (add import GET/POST + export GET routes) |
| Translation keys (admin lang files) | Modify (add import/export labels) |

---

## Out of Scope

- Upsert / update existing zones on re-import (always creates new)
- Per-zone rate customization during import
- Filtering which zones to export
- Import validation preview step
