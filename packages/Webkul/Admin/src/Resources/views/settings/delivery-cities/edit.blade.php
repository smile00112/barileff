<x-admin::layouts>
    <x-slot:title>
        Edit Delivery City
    </x-slot>

    <x-admin::form :action="route('admin.settings.delivery_cities.update', $deliveryCity->id)" method="PUT">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Edit Delivery City
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.settings.delivery_cities.index') }}" class="transparent-button">
                    Back
                </a>

                <button type="submit" class="primary-button">
                    Save
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Code</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="code" rules="required" :value="old('code', $deliveryCity->code)" />
                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Name</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="name" rules="required" :value="old('name', $deliveryCity->name)" />
                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Country</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="country" rules="required" :value="old('country', $deliveryCity->country)" />
                        <x-admin::form.control-group.error control-name="country" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>State</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="state" :value="old('state', $deliveryCity->state)" />
                        <x-admin::form.control-group.error control-name="state" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Center Latitude</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" id="center_lat" name="center_lat" :value="old('center_lat', $deliveryCity->center_lat)" />
                        <x-admin::form.control-group.error control-name="center_lat" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>Center Longitude</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" id="center_lng" name="center_lng" :value="old('center_lng', $deliveryCity->center_lng)" />
                        <x-admin::form.control-group.error control-name="center_lng" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">City Polygon (Yandex Map)</p>

                    <div id="city-map" class="h-[400px] w-full rounded border"></div>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <button type="button" id="set-center-mode" class="secondary-button">Set Center</button>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input id="polygon-edit-mode" type="checkbox" checked />
                            <span>Edit Polygon</span>
                        </label>

                        <button type="button" id="clear-polygon" class="secondary-button">Clear Polygon</button>
                        <button type="button" id="apply-polygon-json" class="secondary-button">Apply JSON</button>
                    </div>

                    <p id="polygon-error" class="mt-2 hidden text-sm text-red-600"></p>

                    <x-admin::form.control-group class="mt-3 !mb-0">
                        <x-admin::form.control-group.label class="required">Polygon JSON</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="textarea" id="polygon_json" name="polygon_json" rules="required" :value="old('polygon_json', json_encode($deliveryCity->polygon_json ?? []))" />
                        <x-admin::form.control-group.error control-name="polygon_json" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex w-[360px] max-w-full flex-col gap-2">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">Settings</p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.control type="hidden" name="is_active" value="0" />
                            <x-admin::form.control-group.control type="switch" name="is_active" value="1" :checked="(bool) old('is_active', $deliveryCity->is_active)" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        @php
            $yandexMapsApiKey = (string) config('services.yandex_maps.api_key', '');
            $yandexMapsScriptUrl = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';

            if ($yandexMapsApiKey !== '') {
                $yandexMapsScriptUrl .= '&apikey='.urlencode($yandexMapsApiKey);
            }
        @endphp

        <script src="{{ $yandexMapsScriptUrl }}"></script>

        <script type="module">
            const initDeliveryCityForm = () => {
                const MIN_POLYGON_VERTEX_COUNT = 3;
                const DEFAULT_CENTER = [55.751244, 37.618423];

                const centerLatInput = document.getElementById('center_lat');
                const centerLngInput = document.getElementById('center_lng');
                const polygonInput = document.getElementById('polygon_json');
                const setCenterModeButton = document.getElementById('set-center-mode');
                const clearPolygonButton = document.getElementById('clear-polygon');
                const applyPolygonJsonButton = document.getElementById('apply-polygon-json');
                const editModeInput = document.getElementById('polygon-edit-mode');
                const polygonError = document.getElementById('polygon-error');

                if (
                    ! (centerLatInput instanceof HTMLInputElement)
                    || ! (centerLngInput instanceof HTMLInputElement)
                    || ! (polygonInput instanceof HTMLTextAreaElement)
                ) {
                    return;
                }

                const showPolygonError = (message) => {
                    if (! polygonError) {
                        return;
                    }

                    if (! message) {
                        polygonError.classList.add('hidden');
                        polygonError.textContent = '';

                        return;
                    }

                    polygonError.textContent = message;
                    polygonError.classList.remove('hidden');
                };

                const parseCenterFromInput = () => {
                    const latitude = Number(centerLatInput.value);
                    const longitude = Number(centerLngInput.value);

                    if (! Number.isFinite(latitude) || ! Number.isFinite(longitude)) {
                        return DEFAULT_CENTER;
                    }

                    return [Number(latitude.toFixed(7)), Number(longitude.toFixed(7))];
                };

                const setCenterToInput = (point) => {
                    centerLatInput.value = Number(point[0]).toFixed(7);
                    centerLngInput.value = Number(point[1]).toFixed(7);
                    centerLatInput.dispatchEvent(new Event('input', { bubbles: true }));
                    centerLngInput.dispatchEvent(new Event('input', { bubbles: true }));
                };

                const syncPolygonInput = (value) => {
                    polygonInput.value = JSON.stringify(value);
                    polygonInput.dispatchEvent(new Event('input', { bubbles: true }));
                    polygonInput.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const normalizePoint = (value) => {
                    if (! Array.isArray(value) || value.length < 2) {
                        throw new Error('Each point must be [latitude, longitude].');
                    }

                    const latitude = Number(value[0]);
                    const longitude = Number(value[1]);

                    if (! Number.isFinite(latitude) || ! Number.isFinite(longitude)) {
                        throw new Error('Latitude and longitude must be numeric.');
                    }

                    return [Number(latitude.toFixed(7)), Number(longitude.toFixed(7))];
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

                const parseCoordinatesFromJson = (jsonValue) => {
                    let parsed;

                    try {
                        parsed = JSON.parse(jsonValue || '[]');
                    } catch (error) {
                        throw new Error('Invalid JSON in Polygon JSON field.');
                    }

                    if (! Array.isArray(parsed)) {
                        throw new Error('Polygon JSON must be an array of coordinates.');
                    }

                    if (! parsed.length) {
                        return [];
                    }

                    let rawCoordinates = parsed;

                    if (Array.isArray(parsed[0]) && parsed[0].length && Array.isArray(parsed[0][0])) {
                        rawCoordinates = parsed[0];
                    }

                    const normalizedCoordinates = rawCoordinates.map(normalizePoint);
                    const coordinatesWithoutClosingPoint = stripClosingPoint(normalizedCoordinates);

                    if (
                        coordinatesWithoutClosingPoint.length > 0
                        && coordinatesWithoutClosingPoint.length < MIN_POLYGON_VERTEX_COUNT
                    ) {
                        throw new Error(`Polygon must have at least ${MIN_POLYGON_VERTEX_COUNT} vertices.`);
                    }

                    return coordinatesWithoutClosingPoint;
                };

                let coordinates;

                try {
                    coordinates = parseCoordinatesFromJson(polygonInput.value);
                } catch (error) {
                    coordinates = [];
                    showPolygonError(error.message);
                    syncPolygonInput(coordinates);
                }

                let map = null;
                let polygonObject = null;
                let centerPlacemark = null;
                let setCenterMode = false;
                let editMode = ! (editModeInput instanceof HTMLInputElement) || editModeInput.checked;

                const updateSetCenterModeButtonState = () => {
                    if (! (setCenterModeButton instanceof HTMLButtonElement)) {
                        return;
                    }

                    setCenterModeButton.textContent = setCenterMode ? 'Set Center (Active)' : 'Set Center';
                };

                const placeCenterMarker = (point) => {
                    if (! map) {
                        return;
                    }

                    if (centerPlacemark) {
                        map.geoObjects.remove(centerPlacemark);
                    }

                    centerPlacemark = new ymaps.Placemark(point, {}, {
                        preset: 'islands#redDotIcon',
                    });

                    map.geoObjects.add(centerPlacemark);
                    setCenterToInput(point);
                };

                const syncCoordinatesFromPolygon = () => {
                    if (! polygonObject) {
                        return;
                    }

                    const geometryCoordinates = polygonObject.geometry.getCoordinates();

                    if (! Array.isArray(geometryCoordinates) || ! Array.isArray(geometryCoordinates[0])) {
                        return;
                    }

                    const normalizedCoordinates = geometryCoordinates[0].map(normalizePoint);
                    coordinates = stripClosingPoint(normalizedCoordinates);
                    syncPolygonInput(coordinates);
                    showPolygonError('');
                };

                const toggleEditorState = () => {
                    if (! polygonObject || ! polygonObject.editor) {
                        return;
                    }

                    if (editMode) {
                        polygonObject.editor.startEditing();
                    } else {
                        polygonObject.editor.stopEditing();
                    }
                };

                const renderPolygon = () => {
                    if (! map) {
                        return;
                    }

                    if (polygonObject) {
                        map.geoObjects.remove(polygonObject);
                        polygonObject = null;
                    }

                    if (coordinates.length < MIN_POLYGON_VERTEX_COUNT) {
                        return;
                    }

                    polygonObject = new ymaps.Polygon([coordinates], {}, {
                        fillColor: '#0077cc33',
                        strokeColor: '#0077cc',
                        strokeWidth: 3,
                    });

                    polygonObject.geometry.events.add('change', () => {
                        syncCoordinatesFromPolygon();
                    });

                    map.geoObjects.add(polygonObject);
                    toggleEditorState();
                };

                setCenterModeButton?.addEventListener('click', () => {
                    setCenterMode = ! setCenterMode;
                    updateSetCenterModeButtonState();
                });

                clearPolygonButton?.addEventListener('click', () => {
                    coordinates = [];
                    syncPolygonInput(coordinates);
                    showPolygonError('');
                    renderPolygon();
                });

                applyPolygonJsonButton?.addEventListener('click', () => {
                    try {
                        coordinates = parseCoordinatesFromJson(polygonInput.value);
                        syncPolygonInput(coordinates);
                        showPolygonError('');
                        renderPolygon();
                    } catch (error) {
                        showPolygonError(error.message);
                    }
                });

                editModeInput?.addEventListener('change', () => {
                    if (editModeInput instanceof HTMLInputElement) {
                        editMode = editModeInput.checked;
                        toggleEditorState();
                    }
                });

                if (typeof ymaps === 'undefined' || typeof ymaps.ready !== 'function') {
                    return;
                }

                ymaps.ready(() => {
                    map = new ymaps.Map('city-map', {
                        center: parseCenterFromInput(),
                        zoom: 10,
                    });

                    placeCenterMarker(parseCenterFromInput());
                    renderPolygon();
                    updateSetCenterModeButtonState();

                    map.events.add('click', (event) => {
                        const point = event.get('coords').map((coordinate) => Number(coordinate.toFixed(7)));

                        if (setCenterMode) {
                            placeCenterMarker(point);
                            setCenterMode = false;
                            updateSetCenterModeButtonState();

                            return;
                        }

                        if (! editMode) {
                            return;
                        }

                        coordinates.push(point);
                        syncPolygonInput(coordinates);
                        showPolygonError('');
                        renderPolygon();
                    });
                });
            };

            window.addEventListener('load', () => {
                window.setTimeout(initDeliveryCityForm, 0);
            });
        </script>
    @endpushOnce
</x-admin::layouts>
