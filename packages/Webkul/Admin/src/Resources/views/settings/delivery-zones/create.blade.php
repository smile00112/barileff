<x-admin::layouts>
    <x-slot:title>
        Create Delivery Zone
    </x-slot>

    <x-admin::form :action="route('admin.settings.delivery_zones.store')">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Create Delivery Zone
            </p>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.settings.delivery_zones.index') }}" class="transparent-button">
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
                        <x-admin::form.control-group.label class="required">City</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="select" name="city_id" rules="required">
                            <option value="">Select city</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </x-admin::form.control-group.control>
                        <x-admin::form.control-group.error control-name="city_id" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Code</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="code" rules="required" :value="old('code')" />
                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">Zone Name</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="name" rules="required" :value="old('name')" />
                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Delivery Time (min)</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="number" name="delivery_time_minutes" :value="old('delivery_time_minutes', 60)" />
                        <x-admin::form.control-group.error control-name="delivery_time_minutes" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>Center Latitude</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="center_lat" :value="old('center_lat')" />
                        <x-admin::form.control-group.error control-name="center_lat" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>Center Longitude</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="text" name="center_lng" :value="old('center_lng')" />
                        <x-admin::form.control-group.error control-name="center_lng" />
                    </x-admin::form.control-group>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">Inventory Sources</p>

                    <div class="grid grid-cols-2 gap-3 max-md:grid-cols-1">
                        @foreach ($inventorySources as $source)
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="inventory_source_ids[]" value="{{ $source->id }}" @checked(collect(old('inventory_source_ids', []))->contains($source->id)) />
                                <span class="text-sm text-gray-700 dark:text-gray-200">{{ $source->name }}</span>
                            </label>
                        @endforeach
                    </div>

                    <x-admin::form.control-group.error control-name="inventory_source_ids" />
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">Zone Rates</p>

                    <div id="rates-wrapper" class="space-y-3">
                        <div class="grid grid-cols-3 gap-2 max-md:grid-cols-1">
                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label class="required">Min Order Total</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" step="0.01" name="rates[0][min_order_total]" value="0" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label class="required">Price</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" step="0.01" name="rates[0][price]" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group class="!mb-0">
                                <x-admin::form.control-group.label>Sort Order</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" name="rates[0][sort_order]" value="0" />
                            </x-admin::form.control-group>
                        </div>
                    </div>

                    <button type="button" id="add-rate-row" class="secondary-button mt-3">
                        Add Rate Row
                    </button>
                </div>

                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">Zone Polygon (Yandex Map)</p>

                    <div id="zone-map" class="h-[400px] w-full rounded border"></div>

                    <div class="mt-3 flex gap-2">
                        <button type="button" id="clear-polygon" class="secondary-button">Clear Polygon</button>
                    </div>

                    <x-admin::form.control-group class="mt-3 !mb-0">
                        <x-admin::form.control-group.label class="required">Polygon JSON</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="textarea" id="polygon_json" name="polygon_json" rules="required" :value="old('polygon_json', '[]')" />
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

        <script type="module">
            const initDeliveryZoneForm = () => {
                let ratesIndex = 1;
                const ratesWrapper = document.getElementById('rates-wrapper');
                const addRateRowButton = document.getElementById('add-rate-row');
                const polygonInput = document.getElementById('polygon_json');
                const clearPolygonButton = document.getElementById('clear-polygon');

                if (! ratesWrapper || ! addRateRowButton || ! polygonInput) {
                    return;
                }

                addRateRowButton.addEventListener('click', () => {
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-3 gap-2 max-md:grid-cols-1';
                    row.innerHTML = `
                        <div>
                            <input class="control w-full" type="number" step="0.01" name="rates[${ratesIndex}][min_order_total]" value="0">
                        </div>
                        <div>
                            <input class="control w-full" type="number" step="0.01" name="rates[${ratesIndex}][price]">
                        </div>
                        <div class="flex gap-2">
                            <input class="control w-full" type="number" name="rates[${ratesIndex}][sort_order]" value="${ratesIndex}">
                            <button type="button" class="secondary-button remove-rate">X</button>
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

                const parseCoordinates = () => {
                    try {
                        const parsed = JSON.parse(polygonInput.value || '[]');

                        if (! Array.isArray(parsed)) {
                            return [];
                        }

                        if (
                            parsed.length > 0
                            && Array.isArray(parsed[0])
                            && parsed[0].length > 0
                            && Array.isArray(parsed[0][0])
                        ) {
                            return parsed[0];
                        }

                        return parsed;
                    } catch (e) {
                        return [];
                    }
                };

                const syncPolygonInput = (value) => {
                    polygonInput.value = JSON.stringify(value);
                    polygonInput.dispatchEvent(new Event('input', { bubbles: true }));
                    polygonInput.dispatchEvent(new Event('change', { bubbles: true }));
                };

                let coordinates = parseCoordinates();
                let polygonObject = null;
                let map = null;

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
                        fillColor: '#0088ff33',
                        strokeColor: '#0077cc',
                        strokeWidth: 3,
                    });

                    map.geoObjects.add(polygonObject);
                };

                clearPolygonButton?.addEventListener('click', () => {
                    coordinates = [];
                    syncPolygonInput(coordinates);
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
                        const point = event.get('coords');
                        coordinates.push([Number(point[0].toFixed(7)), Number(point[1].toFixed(7))]);
                        syncPolygonInput(coordinates);
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
