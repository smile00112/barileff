@php
    $yandexMapsApiKey = (string) config('services.yandex_maps.api_key', '');
    $yandexMapsScriptUrl = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';
    if ($yandexMapsApiKey !== '') {
        $yandexMapsScriptUrl .= '&apikey=' . urlencode($yandexMapsApiKey);
    }
@endphp

<x-shop::layouts>
    <x-slot:title>
        @lang('shop::app.delivery-zones.page-title')
    </x-slot>

    <div class="container mt-8 max-1180:px-5 max-md:mt-6 max-md:px-4">
        <div class="m-auto w-full max-w-[970px]">
            <h1 class="font-dmserif text-4xl max-md:text-3xl max-sm:text-xl">
                @lang('shop::app.delivery-zones.page-title')
            </h1>
            <p class="mt-4 text-xl text-zinc-500 max-sm:mt-1 max-sm:text-sm">
                @lang('shop::app.delivery-zones.select-zone')
            </p>

            <div class="mt-6 grid gap-4 max-md:grid-cols-1 md:grid-cols-2">
                <div>
                    <label for="delivery-city-select" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-gray-300">
                        @lang('shop::app.delivery-zones.city-label')
                    </label>
                    <select
                        id="delivery-city-select"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        aria-label="@lang('shop::app.delivery-zones.city-label')"
                    ></select>
                </div>
                <div>
                    <label for="delivery-address-search" class="mb-1 block text-sm font-medium text-zinc-700 dark:text-gray-300">
                        @lang('shop::app.delivery-zones.address-label')
                    </label>
                    <input
                        type="search"
                        id="delivery-address-search"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        placeholder="@lang('shop::app.delivery-zones.address-placeholder')"
                        autocomplete="off"
                    />
                </div>
            </div>

            <ul
                id="geocode-results"
                class="mt-3 hidden list-none space-y-1 rounded-md border border-gray-200 bg-zinc-50 p-2 text-sm dark:border-gray-600 dark:bg-gray-800"
                role="listbox"
                aria-label="@lang('shop::app.delivery-zones.address-placeholder')"
            ></ul>

            <p id="geocode-hint" class="mt-2 hidden text-sm text-zinc-600 dark:text-gray-400"></p>

            <div
                id="shop-delivery-zones-map"
                class="mt-6 h-[400px] rounded-md border border-gray-300 dark:border-gray-600 max-sm:h-[300px]"
                aria-label="@lang('shop::app.delivery-zones.select-zone')"
            ></div>

            <p id="zone-select-message" class="mt-4 hidden text-sm text-zinc-600 dark:text-gray-400"></p>
        </div>
    </div>

    @pushOnce('scripts')
        <script src="{{ $yandexMapsScriptUrl }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const mapEl = document.getElementById('shop-delivery-zones-map');
                const citySelect = document.getElementById('delivery-city-select');
                const addressInput = document.getElementById('delivery-address-search');
                const geocodeResultsEl = document.getElementById('geocode-results');
                const geocodeHintEl = document.getElementById('geocode-hint');
                const zonesApiUrl = @json(route('shop.api.delivery_zones.index'));
                const selectApiUrl = @json(route('shop.api.delivery_zones.select'));
                const zoneSelectMessage = document.getElementById('zone-select-message');
                const homeUrl = @json(route('shop.home.index'));
                const csrfToken = @json(csrf_token());

                const i18n = {
                    geocodeNoResults: @json(__('shop::app.delivery-zones.geocode-no-results')),
                    geocodeNoMatchesInCity: @json(__('shop::app.delivery-zones.geocode-no-matches-in-city')),
                    geocodeBoundaryDisabled: @json(__('shop::app.delivery-zones.geocode-boundary-disabled')),
                    geocodeError: @json(__('shop::app.delivery-zones.geocode-error')),
                    zonesLoadError: @json(__('shop::app.delivery-zones.zones-load-error')),
                    zoneSelectedSuffix: @json(__('shop::app.delivery-zones.zone-selected-suffix')),
                };

                if (! mapEl || ! citySelect || ! addressInput) {
                    return;
                }

                const showMessage = (text, isError = false) => {
                    if (! zoneSelectMessage) {
                        return;
                    }
                    zoneSelectMessage.textContent = text;
                    zoneSelectMessage.classList.remove('hidden');
                    zoneSelectMessage.classList.toggle('text-red-600', isError);
                    zoneSelectMessage.classList.toggle('text-zinc-600', ! isError);
                };

                const showGeocodeHint = (text, show = true) => {
                    if (! geocodeHintEl) {
                        return;
                    }
                    geocodeHintEl.textContent = text;
                    geocodeHintEl.classList.toggle('hidden', ! show);
                };

                const stripClosingPoint = (value) => {
                    if (value.length < 2) {
                        return value;
                    }
                    const firstPoint = value[0];
                    const lastPoint = value[value.length - 1];
                    if (firstPoint[0] === lastPoint[0] && firstPoint[1] === lastPoint[1]) {
                        return value.slice(0, -1);
                    }

                    return value;
                };

                const normalizePoint = (value) => {
                    if (! Array.isArray(value) || value.length < 2) {
                        return null;
                    }
                    const latitude = Number(value[0]);
                    const longitude = Number(value[1]);
                    if (! Number.isFinite(latitude) || ! Number.isFinite(longitude)) {
                        return null;
                    }

                    return [latitude, longitude];
                };

                const parsePolygonCoordinates = (value) => {
                    if (value === null || value === undefined) {
                        return [];
                    }
                    if (typeof value === 'string') {
                        try {
                            value = JSON.parse(value);
                        } catch (e) {
                            return [];
                        }
                    }
                    if (! Array.isArray(value) || ! value.length) {
                        return [];
                    }
                    let rawCoordinates = value;
                    if (Array.isArray(value[0]) && value[0].length && Array.isArray(value[0][0])) {
                        rawCoordinates = value[0];
                    }
                    const mapped = [];
                    for (let i = 0; i < rawCoordinates.length; i++) {
                        const p = normalizePoint(rawCoordinates[i]);
                        if (p) {
                            mapped.push(p);
                        }
                    }
                    if (mapped.length < 2) {
                        return [];
                    }

                    return stripClosingPoint(mapped);
                };

                const closeRing = (coords) => {
                    if (coords.length < 3) {
                        return coords;
                    }
                    const first = coords[0];
                    const last = coords[coords.length - 1];
                    if (first[0] === last[0] && first[1] === last[1]) {
                        return coords;
                    }

                    return coords.concat([first.slice()]);
                };

                const boundsFromPoints = (points) => {
                    if (! points.length) {
                        return null;
                    }
                    let minLat = points[0][0];
                    let maxLat = points[0][0];
                    let minLng = points[0][1];
                    let maxLng = points[0][1];
                    points.forEach(([lat, lng]) => {
                        minLat = Math.min(minLat, lat);
                        maxLat = Math.max(maxLat, lat);
                        minLng = Math.min(minLng, lng);
                        maxLng = Math.max(maxLng, lng);
                    });

                    return [[minLat, minLng], [maxLat, maxLng]];
                };

                const defaultCenter = [55.751244, 37.618423];
                let citiesData = [];
                let map = null;
                let deliveryLayer = null;
                let searchPlacemark = null;
                let geocodeTimer = null;
                let cityPolygonForFilter = null;
                let cityRingForFilter = null;

                const pointInPolygonRing = (lat, lng, ring) => {
                    if (! ring || ring.length < 3) {
                        return false;
                    }
                    let inside = false;
                    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                        const latI = ring[i][0];
                        const lngI = ring[i][1];
                        const latJ = ring[j][0];
                        const lngJ = ring[j][1];
                        const intersect =
                            (lngI > lng) !== (lngJ > lng) &&
                            lat < ((latJ - latI) * (lng - lngI)) / (lngJ - lngI + 1e-12) + latI;
                        if (intersect) {
                            inside = ! inside;
                        }
                    }

                    return inside;
                };

                ymaps.ready(function() {
                    map = new ymaps.Map('shop-delivery-zones-map', {
                        center: defaultCenter,
                        zoom: 10,
                        controls: ['zoomControl', 'fullscreenControl'],
                    });
                    deliveryLayer = new ymaps.GeoObjectCollection();
                    map.geoObjects.add(deliveryLayer);

                    fetch(zonesApiUrl)
                        .then((r) => r.json())
                        .then((response) => {
                            citiesData = Array.isArray(response.data) ? response.data : [];
                            citySelect.innerHTML = '';
                            citiesData.forEach((city) => {
                                const opt = document.createElement('option');
                                opt.value = String(city.id);
                                opt.textContent = city.name;
                                citySelect.appendChild(opt);
                            });
                            if (citiesData.length) {
                                citySelect.value = String(citiesData[0].id);
                                renderCityOnMap(citiesData[0]);
                            }
                        })
                        .catch(() => showMessage(i18n.zonesLoadError, true));

                    citySelect.addEventListener('change', function() {
                        const city = citiesData.find((c) => String(c.id) === citySelect.value);
                        if (city) {
                            renderCityOnMap(city);
                        }
                        addressInput.value = '';
                        clearGeocodeUi();
                    });

                    addressInput.addEventListener('input', function() {
                        clearTimeout(geocodeTimer);
                        const q = addressInput.value.trim();
                        if (! q) {
                            clearGeocodeUi();

                            return;
                        }
                        geocodeTimer = setTimeout(() => runGeocode(q), 2000);
                    });
                });

                function clearGeocodeUi() {
                    if (geocodeResultsEl) {
                        geocodeResultsEl.innerHTML = '';
                        geocodeResultsEl.classList.add('hidden');
                    }
                    showGeocodeHint('', false);
                    if (map && searchPlacemark) {
                        map.geoObjects.remove(searchPlacemark);
                        searchPlacemark = null;
                    }
                }

                function getSelectedCity() {
                    return citiesData.find((c) => String(c.id) === citySelect.value) || null;
                }

                function renderCityOnMap(city) {
                    if (! map || ! deliveryLayer) {
                        return;
                    }
                    deliveryLayer.removeAll();
                    cityPolygonForFilter = null;
                    cityRingForFilter = null;
                    const centerLat = Number(city.center_lat) || defaultCenter[0];
                    const centerLng = Number(city.center_lng) || defaultCenter[1];
                    const allBoundsPoints = [];

                    const cityRing = parsePolygonCoordinates(city.polygon_json);
                    if (cityRing.length >= 3) {
                        const closedCity = closeRing(cityRing);
                        const cityPoly = new ymaps.Polygon([closedCity], {}, {
                            fillColor: '#6b728033',
                            strokeColor: '#4b5563',
                            strokeWidth: 2,
                            cursor: 'default',
                        });
                        deliveryLayer.add(cityPoly);
                        cityPolygonForFilter = cityPoly;
                        cityRingForFilter = closedCity;
                        closedCity.forEach((p) => allBoundsPoints.push(p));
                    }

                    (city.zones || []).forEach((zone) => {
                        const coords = parsePolygonCoordinates(zone.polygon_json);
                        if (coords.length < 3) {
                            return;
                        }
                        const ring = closeRing(coords);
                        ring.forEach((p) => allBoundsPoints.push(p));
                        const fillColor = (zone.polygon_color || '#0077cc') + '33';
                        const polygon = new ymaps.Polygon([ring], {}, {
                            fillColor: fillColor,
                            strokeColor: zone.polygon_color || '#0077cc',
                            strokeWidth: 2,
                            cursor: 'pointer',
                        });
                        polygon.events.add('click', function() {
                            const formData = new FormData();
                            formData.append('delivery_zone_id', zone.id);
                            formData.append('_token', csrfToken);

                            fetch(selectApiUrl, {
                                method: 'POST',
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: formData,
                            })
                                .then((r) => r.json())
                                .then((data) => {
                                    if (data.data?.zone) {
                                        showMessage(zone.name + ' ' + i18n.zoneSelectedSuffix);
                                        setTimeout(() => {
                                            window.location.href = homeUrl;
                                        }, 800);
                                    } else if (data.data?.message) {
                                        showMessage(data.data.message, true);
                                    }
                                })
                                .catch(() => showMessage(i18n.zonesLoadError, true));
                        });
                        deliveryLayer.add(polygon);
                    });

                    if (allBoundsPoints.length) {
                        const b = boundsFromPoints(allBoundsPoints);
                        map.setBounds(b, { checkZoomRange: true, zoomMargin: 50 });
                    } else {
                        map.setCenter([centerLat, centerLng], 11);
                    }
                }

                function pointInsideCityPolygon(coords) {
                    if (! coords || coords.length < 2) {
                        return false;
                    }
                    const lat = coords[0];
                    const lng = coords[1];
                    if (cityPolygonForFilter) {
                        const geometry = cityPolygonForFilter.geometry;
                        if (geometry && typeof geometry.contains === 'function') {
                            try {
                                return geometry.contains(coords);
                            } catch (e) {
                                /* fallback: ray casting */
                            }
                        }
                    }
                    if (cityRingForFilter && cityRingForFilter.length >= 3) {
                        return pointInPolygonRing(lat, lng, cityRingForFilter);
                    }

                    return false;
                }

                function runGeocode(query) {
                    const city = getSelectedCity();
                    if (! city || ! map) {
                        return;
                    }

                    const cityRing = parsePolygonCoordinates(city.polygon_json);
                    const hasBoundary = cityRing.length >= 3;

                    if (! hasBoundary) {
                        showGeocodeHint(i18n.geocodeBoundaryDisabled, true);
                    } else {
                        showGeocodeHint('', false);
                    }

                    const geoOpts = { results: 10 };
                    if (hasBoundary && cityPolygonForFilter) {
                        try {
                            const bounds = cityPolygonForFilter.geometry && cityPolygonForFilter.geometry.getBounds
                                ? cityPolygonForFilter.geometry.getBounds()
                                : null;
                            if (bounds) {
                                geoOpts.boundedBy = bounds;
                                geoOpts.strictBounds = false;
                            }
                        } catch (e) {
                            /* ignore */
                        }
                    }

                    ymaps.geocode(query, geoOpts)
                        .then((res) => {
                            const results = [];
                            res.geoObjects.each((geoObject) => {
                                const coords = geoObject.geometry.getCoordinates();
                                if (! coords || coords.length < 2) {
                                    return;
                                }
                                const lat = coords[0];
                                const lng = coords[1];
                                const text = geoObject.getAddressLine() || geoObject.properties.get('name') || query;
                                if (hasBoundary) {
                                    if (pointInsideCityPolygon([lat, lng])) {
                                        results.push({ text, coords: [lat, lng] });
                                    }
                                } else {
                                    results.push({ text, coords: [lat, lng] });
                                }
                            });

                            if (! geocodeResultsEl) {
                                return;
                            }
                            geocodeResultsEl.innerHTML = '';
                            if (! results.length) {
                                geocodeResultsEl.classList.remove('hidden');
                                const li = document.createElement('li');
                                li.className = 'rounded px-2 py-1 text-zinc-600 dark:text-gray-400';
                                li.textContent = hasBoundary ? i18n.geocodeNoMatchesInCity : i18n.geocodeNoResults;
                                geocodeResultsEl.appendChild(li);

                                return;
                            }
                            geocodeResultsEl.classList.remove('hidden');
                            results.forEach((item, index) => {
                                const li = document.createElement('li');
                                li.setAttribute('role', 'option');
                                li.tabIndex = 0;
                                li.className =
                                    'cursor-pointer rounded px-2 py-1.5 hover:bg-white dark:hover:bg-gray-700';
                                li.textContent = item.text;
                                li.addEventListener('click', () => focusGeocodeResult(item.coords, item.text));
                                li.addEventListener('keydown', (e) => {
                                    if (e.key === 'Enter' || e.key === ' ') {
                                        e.preventDefault();
                                        focusGeocodeResult(item.coords, item.text);
                                    }
                                });
                                geocodeResultsEl.appendChild(li);
                            });
                        })
                        .catch(() => {
                            showGeocodeHint(i18n.geocodeError, true);
                        });
                }

                function focusGeocodeResult(coords, title) {
                    if (! map) {
                        return;
                    }
                    if (searchPlacemark) {
                        map.geoObjects.remove(searchPlacemark);
                    }
                    searchPlacemark = new ymaps.Placemark(coords, { balloonContent: title }, {
                        preset: 'islands#blueDotIcon',
                    });
                    map.geoObjects.add(searchPlacemark);
                    map.setCenter(coords, 16);
                }
            });
        </script>
    @endPushOnce
</x-shop::layouts>
