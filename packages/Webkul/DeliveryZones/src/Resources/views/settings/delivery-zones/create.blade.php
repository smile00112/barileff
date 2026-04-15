<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.delivery_zones.zones-create.title')
    </x-slot>

    <x-admin::form :action="route('admin.settings.delivery_zones.store')">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.settings.delivery_zones.zones-create.heading')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.settings.delivery_zones.index') }}" class="transparent-button">
                    @lang('admin::app.settings.delivery_zones.edit.back-btn')
                </a>

                <button type="submit" class="primary-button">
                    @lang('admin::app.settings.delivery_zones.edit.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.city')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="city_id" rules="required">
                            <option value="">@lang('admin::app.settings.delivery_zones.edit.select-city')</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="city_id" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.code')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="code" rules="required" :value="old('code')" />
                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.zone-name')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="name" rules="required" :value="old('name')" />
                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>@lang('admin::app.settings.delivery_zones.edit.delivery-time-min')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="number" name="delivery_time_minutes" :value="old('delivery_time_minutes', 60)" />
                        <x-admin::form.control-group.error control-name="delivery_time_minutes" />
                    </x-admin::form.control-group>

                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.delivery_zones.edit.inventory-sources')</p>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.inventory-source')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="inventory_source_ids" rules="required">
                            <option value="">@lang('admin::app.settings.delivery_zones.edit.select-inventory-source')</option>

                            @foreach ($inventorySources as $source)
                                <option value="{{ $source->id }}" @selected((int) old('inventory_source_ids') === $source->id)>
                                    {{ $source->name }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>

                    <x-admin::form.control-group.error control-name="inventory_source_ids" />
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.delivery_zones.edit.zone-rates')</p>

                    <div id="rates-wrapper" class="space-y-3">
                        <div class="grid grid-cols-4 gap-2 max-md:grid-cols-1">
                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.min-order-total')</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" step="0.01" name="rates[0][min_order_total]" value="0" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.price')</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" step="0.01" name="rates[0][price]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label>@lang('admin::app.settings.delivery_zones.edit.sort-order')</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" name="rates[0][sort_order]" value="0" />
                            </x-admin::form.control-group>

                            <div class="flex items-end">
                                <button type="button" class="secondary-button remove-rate">@lang('admin::app.settings.delivery_zones.edit.remove-rate')</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="add-rate-row" class="secondary-button mt-3">
                        @lang('admin::app.settings.delivery_zones.edit.add-rate-row')
                    </button>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.delivery_zones.edit.zone-polygon')</p>

                    <div id="zone-map" class="h-[400px] w-full rounded border"></div>

                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input id="polygon-edit-mode" type="checkbox" checked />
                            <span>@lang('admin::app.settings.delivery_zones.edit.edit-mode')</span>
                        </label>

                        <button type="button" id="clear-polygon" class="secondary-button">@lang('admin::app.settings.delivery_zones.edit.clear-polygon')</button>
                        <button type="button" id="apply-polygon-json" class="secondary-button">@lang('admin::app.settings.delivery_zones.edit.apply-polygon-json')</button>
                    </div>

                    <p id="polygon-error" class="mt-2 hidden text-sm text-red-600"></p>

                    <x-admin::form.control-group class="mt-3 !mb-0">
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.polygon-json')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="textarea" id="polygon_json" name="polygon_json" rules="required" :value="old('polygon_json', '[]')" />
                        <x-admin::form.control-group.error control-name="polygon_json" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mt-3 !mb-0">
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.polygon-color')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="color" id="polygon_color" name="polygon_color" rules="required" :value="old('polygon_color', '#0077cc')" />
                        <x-admin::form.control-group.error control-name="polygon_color" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mt-3 !mb-0">
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.polygon-fill-opacity')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="number" id="polygon_fill_opacity" name="polygon_fill_opacity" min="0" max="1" step="0.01" rules="required" :value="old('polygon_fill_opacity', 0.20)" />
                        <x-admin::form.control-group.error control-name="polygon_fill_opacity" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="mt-3 !mb-0">
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.border-opacity')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="number" id="polygon_stroke_opacity" name="polygon_stroke_opacity" min="0" max="1" step="0.01" rules="required" :value="old('polygon_stroke_opacity', 1)" />
                        <x-admin::form.control-group.error control-name="polygon_stroke_opacity" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="flex w-[360px] max-w-full flex-col gap-2">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">@lang('admin::app.settings.delivery_zones.edit.settings')</p>
                    </x-slot>

                    <x-slot:content>
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.control type="hidden" name="is_active" value="0" />
                            <x-admin::form.control-group.control type="switch" name="is_active" value="1" :checked="true" />
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

            $yandexMapsSuggestApiKey = (string) config('services.yandex_maps.suggest_api_key', '');
            if ($yandexMapsSuggestApiKey !== '') {
                $yandexMapsScriptUrl .= '&suggest_apikey='.urlencode($yandexMapsSuggestApiKey);
            }
        @endphp

        <script src="{{ $yandexMapsScriptUrl }}"></script>

        @php
            $deliveryZonePolygonJs = [
                'invalid_json' => __('admin::app.settings.delivery_zones.js.invalid-json-polygon-field'),
                'polygon_must_be_array' => __('admin::app.settings.delivery_zones.js.polygon-must-be-coordinate-array'),
                'point_must_be_lat_lng' => __('admin::app.settings.delivery_zones.js.point-must-be-lat-lng'),
                'lat_lng_numeric' => __('admin::app.settings.delivery_zones.js.lat-lng-numeric'),
                'polygon_min_vertices' => __('admin::app.settings.delivery_zones.js.polygon-min-vertices'),
                'remove_rate' => __('admin::app.settings.delivery_zones.edit.remove-rate'),
            ];
        @endphp

        <script type="module">
            const initDeliveryZoneForm = () => {
                const polygonJs = @json($deliveryZonePolygonJs);
                const MIN_POLYGON_VERTEX_COUNT = 3;
                let ratesIndex = 1;
                const ratesWrapper = document.getElementById('rates-wrapper');
                const addRateRowButton = document.getElementById('add-rate-row');
                const polygonInput = document.getElementById('polygon_json');
                const polygonColorInput = document.getElementById('polygon_color');
                const polygonFillOpacityInput = document.getElementById('polygon_fill_opacity');
                const polygonStrokeOpacityInput = document.getElementById('polygon_stroke_opacity');
                const clearPolygonButton = document.getElementById('clear-polygon');
                const applyPolygonJsonButton = document.getElementById('apply-polygon-json');
                const editModeInput = document.getElementById('polygon-edit-mode');
                const polygonError = document.getElementById('polygon-error');

                if (! ratesWrapper || ! addRateRowButton || ! polygonInput) {
                    return;
                }

                addRateRowButton.addEventListener('click', () => {
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-4 gap-2 max-md:grid-cols-1';
                    row.innerHTML = `
                        <div>
                            <input class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400" type="number" step="0.01" name="rates[${ratesIndex}][min_order_total]" value="0">
                        </div>
                        <div>
                            <input class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400" type="number" step="0.01" name="rates[${ratesIndex}][price]">
                        </div>
                        <div class="flex gap-2">
                            <input class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400" type="number" name="rates[${ratesIndex}][sort_order]" value="${ratesIndex}">
                        </div>
                        <div class="flex items-end">
                            <button type="button" class="secondary-button remove-rate">${polygonJs.remove_rate}</button>
                        </div>
                    `;

                    ratesWrapper.appendChild(row);
                    ratesIndex++;
                });

                ratesWrapper.addEventListener('click', (event) => {
                    const target = event.target;

                    if (target instanceof HTMLElement && target.classList.contains('remove-rate')) {
                        target.closest('.grid')?.remove();
                    }
                });

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

                const syncPolygonInput = (value) => {
                    polygonInput.value = JSON.stringify(value);
                    polygonInput.dispatchEvent(new Event('input', { bubbles: true }));
                    polygonInput.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const normalizePolygonColor = (value) => {
                    if (typeof value !== 'string') {
                        return '#0077cc';
                    }

                    const normalized = value.trim();

                    if (! /^#[0-9A-Fa-f]{6}$/.test(normalized)) {
                        return '#0077cc';
                    }

                    return normalized.toLowerCase();
                };

                const getPolygonColor = () => {
                    if (! (polygonColorInput instanceof HTMLInputElement)) {
                        return '#0077cc';
                    }

                    const normalizedColor = normalizePolygonColor(polygonColorInput.value);
                    polygonColorInput.value = normalizedColor;

                    return normalizedColor;
                };

                const normalizeOpacity = (value, fallbackValue) => {
                    const normalized = Number.parseFloat(String(value));

                    if (! Number.isFinite(normalized)) {
                        return fallbackValue;
                    }

                    return Math.min(1, Math.max(0, normalized));
                };

                const getHexAlpha = (value, fallbackValue) => {
                    const opacity = normalizeOpacity(value, fallbackValue);
                    const alpha = Math.round(opacity * 255);

                    return alpha.toString(16).padStart(2, '0');
                };

                const getFillOpacityHex = () => {
                    if (! (polygonFillOpacityInput instanceof HTMLInputElement)) {
                        return getHexAlpha(0.2, 0.2);
                    }

                    const opacity = normalizeOpacity(polygonFillOpacityInput.value, 0.2);
                    polygonFillOpacityInput.value = opacity.toFixed(2);

                    return getHexAlpha(opacity, 0.2);
                };

                const getStrokeOpacityHex = () => {
                    if (! (polygonStrokeOpacityInput instanceof HTMLInputElement)) {
                        return getHexAlpha(1, 1);
                    }

                    const opacity = normalizeOpacity(polygonStrokeOpacityInput.value, 1);
                    polygonStrokeOpacityInput.value = opacity.toFixed(2);

                    return getHexAlpha(opacity, 1);
                };

                const normalizePoint = (value) => {
                    if (! Array.isArray(value) || value.length < 2) {
                        throw new Error(polygonJs.point_must_be_lat_lng);
                    }

                    const latitude = Number(value[0]);
                    const longitude = Number(value[1]);

                    if (! Number.isFinite(latitude) || ! Number.isFinite(longitude)) {
                        throw new Error(polygonJs.lat_lng_numeric);
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
                        throw new Error(polygonJs.invalid_json);
                    }

                    if (! Array.isArray(parsed)) {
                        throw new Error(polygonJs.polygon_must_be_array);
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
                        throw new Error(polygonJs.polygon_min_vertices.replace(':count', String(MIN_POLYGON_VERTEX_COUNT)));
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

                let polygonObject = null;
                let map = null;
                let editMode = ! (editModeInput instanceof HTMLInputElement) || editModeInput.checked;

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

                    if (coordinates.length < 3) {
                        return;
                    }

                    polygonObject = new ymaps.Polygon([coordinates], {}, {
                        fillColor: `${getPolygonColor()}${getFillOpacityHex()}`,
                        strokeColor: `${getPolygonColor()}${getStrokeOpacityHex()}`,
                        strokeWidth: 3,
                    });

                    polygonObject.geometry.events.add('change', () => {
                        syncCoordinatesFromPolygon();
                    });

                    map.geoObjects.add(polygonObject);
                    toggleEditorState();
                };

                clearPolygonButton?.addEventListener('click', () => {
                    coordinates = [];
                    syncPolygonInput(coordinates);
                    showPolygonError('');
                    renderPolygon();
                });

                applyPolygonJsonButton?.addEventListener('click', () => {
                    try {
                        const parsedCoordinates = parseCoordinatesFromJson(polygonInput.value);
                        coordinates = parsedCoordinates;
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

                polygonColorInput?.addEventListener('input', () => {
                    renderPolygon();
                });

                polygonFillOpacityInput?.addEventListener('input', () => {
                    renderPolygon();
                });

                polygonStrokeOpacityInput?.addEventListener('input', () => {
                    renderPolygon();
                });

                if (typeof ymaps === 'undefined' || typeof ymaps.ready !== 'function') {
                    return;
                }

                ymaps.ready(() => {
                    map = new ymaps.Map('zone-map', {
                        center: [55.751244, 37.618423],
                        zoom: 10,
                    });

                    renderPolygon();

                    map.events.add('click', (event) => {
                        if (! editMode) {
                            return;
                        }

                        const point = event.get('coords');
                        coordinates.push([Number(point[0].toFixed(7)), Number(point[1].toFixed(7))]);
                        syncPolygonInput(coordinates);
                        showPolygonError('');
                        renderPolygon();
                    });
                });
            };

            window.addEventListener('load', () => {
                window.setTimeout(initDeliveryZoneForm, 0);
            });
        </script>
    @endpushOnce
</x-admin::layouts>
