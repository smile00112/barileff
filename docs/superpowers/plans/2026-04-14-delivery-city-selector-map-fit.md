# Delivery City Selector — Map Fit-to-Zones Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When a city is selected in the delivery modal, fit the map viewport to encompass all polygon zones of that city instead of using a fixed zoom of 11.

**Architecture:** Add two private helper methods (`_getCityBounds`, `_flyToCity`) to the Vue component, then replace the existing `setCenter(..., 11)` calls in `changeCity()` and `initMap()` with `_flyToCity()`. No backend changes, no new API endpoints, no UI changes.

**Tech Stack:** Yandex Maps API 2.1 (ymaps), Vue 3 component embedded in a Laravel Blade template.

---

## Files

| Action | Path |
|--------|------|
| Modify | `packages/Webkul/Shop/src/Resources/views/components/layouts/header/delivery-method-selector.blade.php` |

---

### Task 1: Add `_getCityBounds` and `_flyToCity` helper methods

These two methods are added to the Vue component's `methods` object. Place them just before the existing `changeCity` method (line ~1288).

**Files:**
- Modify: `packages/Webkul/Shop/src/Resources/views/components/layouts/header/delivery-method-selector.blade.php`

- [ ] **Step 1: Add `_getCityBounds(cityId)` method**

Find the `changeCity(cityId) {` line (~line 1288) and insert the following two methods immediately before it:

```javascript
                _getCityBounds(cityId) {
                    const cityZones = this.allZones.filter((zone) => zone.city_id === cityId);
                    const points = [];

                    for (const zone of cityZones) {
                        const coords = this.parseCoordinatesValue(zone.polygon_json);

                        for (const point of coords) {
                            points.push(point);
                        }
                    }

                    if (points.length < 3) {
                        return null;
                    }

                    const lats = points.map((p) => p[0]);
                    const lngs = points.map((p) => p[1]);

                    return [
                        [Math.min(...lats), Math.min(...lngs)],
                        [Math.max(...lats), Math.max(...lngs)],
                    ];
                },

                _flyToCity(city) {
                    if (!_ymapsRaw.map || !city) {
                        return;
                    }

                    const bounds = this._getCityBounds(city.id);

                    if (bounds) {
                        _ymapsRaw.map.setBounds(bounds, {
                            checkZoomRange: true,
                            duration: 300,
                            margin: [40, 40, 40, 40],
                        });
                        return;
                    }

                    if (city.center_lat && city.center_lng) {
                        _ymapsRaw.map.setCenter([city.center_lat, city.center_lng], 11, {
                            checkZoomRange: true,
                            duration: 300,
                        });
                        return;
                    }

                    _ymapsRaw.map.setCenter([40.1872, 44.5152], 11);
                },

```

- [ ] **Step 2: Verify the insertion looks right**

Confirm the methods object now reads:
```
..._getCityBounds(cityId) { ... },
..._flyToCity(city) { ... },
...changeCity(cityId) { ... },
```

---

### Task 2: Update `changeCity` to use `_flyToCity`

**Files:**
- Modify: `packages/Webkul/Shop/src/Resources/views/components/layouts/header/delivery-method-selector.blade.php:1288`

- [ ] **Step 1: Replace the `setCenter` block in `changeCity`**

Current code in `changeCity` (~lines 1292–1297):
```javascript
                    if (city?.center_lat && city?.center_lng && _ymapsRaw.map) {
                        _ymapsRaw.map.setCenter([city.center_lat, city.center_lng], 11, {
                            checkZoomRange: true,
                            duration: 300,
                        });
                    }
```

Replace with:
```javascript
                    this._flyToCity(city);
```

The full `changeCity` method should now look like:
```javascript
                changeCity(cityId) {
                    this.selectedCityId = Number(cityId);
                    const city = this.cities.find((c) => c.id === this.selectedCityId);

                    this._flyToCity(city);

                    this.selectedZone = null;
                    this.resolvedZone = null;
                    this.addressOutsideZone = false;
                    this.deliveryQuery = '';
                    this.clearPin();
                    this.hideSuggestions();
                    this.updateZoneHighlight();
                },
```

---

### Task 3: Update `initMap` to fit the initial city after polygons are drawn

**Files:**
- Modify: `packages/Webkul/Shop/src/Resources/views/components/layouts/header/delivery-method-selector.blade.php:859`

- [ ] **Step 1: Add `_flyToCity` call after `addZonePolygons()`**

Current code (~lines 859–870):
```javascript
                            this.addZonePolygons();

                            if (this.currentAddressCoords) {
                                this.addOrMoveMarker(this.currentAddressCoords);
                                _ymapsRaw.map.setCenter(this.toPlainCoords(this.currentAddressCoords), 16, {
                                    checkZoomRange: true,
                                    duration: 0,
                                });
                            }

                            this.mapReady = true;
```

Replace with:
```javascript
                            this.addZonePolygons();

                            if (this.currentAddressCoords) {
                                this.addOrMoveMarker(this.currentAddressCoords);
                                _ymapsRaw.map.setCenter(this.toPlainCoords(this.currentAddressCoords), 16, {
                                    checkZoomRange: true,
                                    duration: 0,
                                });
                            } else if (this.activeCity) {
                                this._flyToCity(this.activeCity);
                            }

                            this.mapReady = true;
```

The `else if` ensures that if a pinned address is already restored (e.g. the user had a saved address from a previous session), the map zooms to that address rather than the city bounds. If no pinned address exists, it fits to the city.

---

### Task 4: Manual verification and commit

- [ ] **Step 1: Open the storefront, open the delivery modal**

Verify:
- Map opens already fitted to the first city's zone polygons (not a generic zoom-11 view).
- If the first city has no polygons, it falls back to the city's `center_lat`/`center_lng` at zoom 11.

- [ ] **Step 2: Switch to another city in the dropdown (if multiple cities exist)**

Verify:
- Map pans and zooms to fit that city's zones.
- Address input, zone selection, and pin are cleared.
- All cities' zone polygons remain visible on the map.

- [ ] **Step 3: Test fallback — city with no valid zones**

If you have a city with no zones (or all polygons empty), selecting it should move the map to that city's `center_lat`/`center_lng` at zoom 11 (or the default coordinates `[40.1872, 44.5152]` if center coords are also null).

- [ ] **Step 4: Test with a restored address**

If the browser has a previously saved delivery address in `localStorage` (key `delivery-selector-active-address`), opening the modal should still zoom to the pinned address at zoom 16, not to city bounds. Clear `localStorage` to verify the city-bounds path.

- [ ] **Step 5: Commit**

```bash
git add packages/Webkul/Shop/src/Resources/views/components/layouts/header/delivery-method-selector.blade.php
git commit -m "feat(delivery-zones): fit map to city zone bounds on city selection"
```
