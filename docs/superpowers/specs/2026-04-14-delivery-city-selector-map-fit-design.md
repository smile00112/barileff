# Design: Delivery City Selector — Map Fit-to-Zones

**Date:** 2026-04-14  
**Branch:** main  
**Scope:** Frontend-only, single file

---

## Problem

The delivery address modal has a city dropdown that already exists and works (shows when `cities.length > 1`, defaults to the first city). When a city is selected, the map moves to the city's `center_lat`/`center_lng` at a fixed zoom of 11. This ignores how large or small the city's zones actually are, making navigation awkward.

## Goal

When a city is selected (including on initial load), fit the map viewport to encompass all polygon zones of that city. All cities' zones remain visible on the map — only the pan/zoom changes.

---

## Approach

**Pure JS, no backend changes.** Compute bounding box from zone polygon coordinates already present in `this.allZones`.

---

## Changes

All changes are in one file:
`packages/Webkul/Shop/src/Resources/views/components/layouts/header/delivery-method-selector.blade.php`

### 1. New method: `_getCityBounds(cityId)`

- Filter `this.allZones` by `zone.city_id === cityId`
- For each zone, call the existing `parseCoordinatesValue(zone.polygon_json)` to get `[lat, lng]` pairs
- Collect all valid points across all zones
- Return `[[minLat, minLng], [maxLat, maxLng]]` if at least 3 valid points exist
- Return `null` if no valid points (city has no zones or all polygons are malformed)

### 2. New method: `_flyToCity(city)`

Encapsulates the "move map to city" logic used in both `changeCity()` and `initMap()`:

```
bounds = _getCityBounds(city.id)
if bounds:
    map.setBounds(bounds, { checkZoomRange: true, duration: 300, margin: [40,40,40,40] })
elif city.center_lat && city.center_lng:
    map.setCenter([city.center_lat, city.center_lng], 11, { checkZoomRange: true, duration: 300 })
else:
    map.setCenter([default_coords], 11)
```

Default coords fallback: `[40.1872, 44.5152]` (same as current `initMap()` default).

### 3. Update `changeCity(cityId)`

Replace the existing `_ymapsRaw.map.setCenter([...], 11, ...)` call with `this._flyToCity(city)`. The rest of `changeCity()` (reset zone/address/pin) stays unchanged.

### 4. Update `initMap()`

After the map is initialized and `addZonePolygons()` is called, if `this.activeCity` is set, call `this._flyToCity(this.activeCity)`. This replaces the current fixed `center + zoom 11` used during initialization.

If `this.currentAddressCoords` is set (restored persisted address), skip `_flyToCity` and keep the existing behavior (zoom to the pinned address at zoom 16).

---

## Fallback Chain

```
has valid zone polygons?  → setBounds (fit to all city zones)
else has center_lat/lng?  → setCenter at zoom 11
else                      → setCenter at default coords, zoom 11
```

---

## No Changes To

- Backend / API endpoints
- City dropdown UI (already works, shown when `cities.length > 1`)
- Zone polygon rendering (all cities' zones stay visible)
- Default first-city selection in `mounted()` (already implemented)
- Any other files

---

## Testing

Manual:
1. Open delivery modal with multiple cities → map should fit to first city's zones
2. Switch to another city → map pans/zooms to fit that city's zones
3. City with no zones → falls back to city center coords at zoom 11
4. City with neither zones nor center coords → falls back to default coords

No automated tests needed (pure UI/map behavior with no business logic changes).
