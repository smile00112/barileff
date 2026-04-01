<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.delivery_zones.zones.manage-title') - {{ $deliveryCity->name }}
    </x-slot>

    @php
        $zones = $deliveryCity->zones->sortBy('name')->values();

        $zonesPayload = $zones->map(function ($zone) {
            return [
                'id' => (int) $zone->id,
                'city_id' => (int) $zone->city_id,
                'code' => (string) $zone->code,
                'name' => (string) $zone->name,
                'polygon_json' => $zone->polygon_json ?? [],
                'polygon_color' => (string) ($zone->polygon_color ?? '#0077cc'),
                'polygon_fill_opacity' => (float) ($zone->polygon_fill_opacity ?? 0.2),
                'polygon_stroke_opacity' => (float) ($zone->polygon_stroke_opacity ?? 1),
                'delivery_time_minutes' => $zone->delivery_time_minutes !== null ? (int) $zone->delivery_time_minutes : null,
                'is_active' => (bool) $zone->is_active,
                'inventory_source_id' => (int) ($zone->inventory_sources->pluck('id')->first() ?? 0),
                'rates' => $zone->rates
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn ($rate) => [
                        'min_order_total' => (float) $rate->min_order_total,
                        'price' => (float) $rate->price,
                        'sort_order' => (int) $rate->sort_order,
                    ])
                    ->all(),
            ];
        })->all();

        $oldZonePayload = null;

        if (old('code') !== null || old('name') !== null || old('polygon_json') !== null) {
            $oldZonePayload = [
                'id' => old('zone_id') ? (int) old('zone_id') : null,
                'city_id' => (int) old('city_id', $deliveryCity->id),
                'code' => (string) old('code', ''),
                'name' => (string) old('name', ''),
                'polygon_json' => old('polygon_json', '[]'),
                'polygon_color' => (string) old('polygon_color', '#0077cc'),
                'polygon_fill_opacity' => (float) old('polygon_fill_opacity', 0.2),
                'polygon_stroke_opacity' => (float) old('polygon_stroke_opacity', 1),
                'delivery_time_minutes' => old('delivery_time_minutes') !== null ? (int) old('delivery_time_minutes') : null,
                'is_active' => (bool) old('is_active', 1),
                'inventory_source_id' => (int) old('inventory_source_ids', 0),
                'rates' => is_array(old('rates'))
                    ? array_values(array_map(
                        fn ($rate, $index) => [
                            'min_order_total' => (float) ($rate['min_order_total'] ?? 0),
                            'price' => (float) ($rate['price'] ?? 0),
                            'sort_order' => (int) ($rate['sort_order'] ?? $index),
                        ],
                        old('rates'),
                        array_keys(old('rates'))
                    ))
                    : [
                        [
                            'min_order_total' => 0,
                            'price' => 0,
                            'sort_order' => 0,
                        ],
                    ],
            ];
        }

        $yandexMapsApiKey = (string) config('services.yandex_maps.api_key', '');
        $yandexMapsScriptUrl = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';

        if ($yandexMapsApiKey !== '') {
            $yandexMapsScriptUrl .= '&apikey='.urlencode($yandexMapsApiKey);
        }
    @endphp

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.settings.delivery_zones.zones.manage-title')
            </p>

            <p class="text-sm text-gray-600 dark:text-gray-300">
                @lang('admin::app.settings.delivery_zones.zones.city-label'): {{ $deliveryCity->name }}
            </p>
        </div>

        <div class="flex items-center gap-x-2.5">
            <a href="{{ route('admin.settings.delivery_cities.index') }}" class="transparent-button">
                @lang('admin::app.settings.delivery_zones.zones.back-to-cities')
            </a>

            <a href="{{ route('admin.settings.delivery_cities.edit', $deliveryCity->id) }}" class="secondary-button">
                @lang('admin::app.settings.delivery_zones.zones.edit-city')
            </a>
        </div>
    </div>

    <div class="mt-3.5 grid grid-cols-[380px,minmax(0,1fr)] gap-3.5 max-xl:grid-cols-1">
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-3 flex items-center justify-between gap-2 px-6 py-4">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    @lang('admin::app.settings.delivery_zones.zones.city-zones')
                </p>

                <button type="button" id="add-zone-button" class="secondary-button">
                    @lang('admin::app.settings.delivery_zones.zones.add-new-zone')
                </button>
            </div>

            <div id="zones-list" class="space-y-2 px-6 pb-6">
                @forelse ($zones as $zone)
                    @php
                        $zoneColor = strtolower((string) ($zone->polygon_color ?? '#0077cc'));
                        $fillOpacity = (float) ($zone->polygon_fill_opacity ?? 0.2);
                    @endphp

                    <div class="rounded border border-gray-200 dark:border-gray-700" data-zone-item="{{ $zone->id }}">
                        <div class="flex items-center gap-2 p-2">
                            <button
                                type="button"
                                class="zone-select-button flex min-w-0 flex-1 items-center gap-2 rounded px-2 py-1 text-left hover:bg-gray-100 dark:hover:bg-gray-800"
                                data-zone-id="{{ $zone->id }}"
                            >
                                <span
                                    class="inline-block h-4 w-4 rounded border border-gray-400"
                                    style="background-color: {{ $zoneColor }}; opacity: {{ $fillOpacity }};"
                                ></span>

                                <span class="truncate text-sm font-medium text-gray-800 dark:text-white">
                                    {{ $zone->name }}
                                </span>
                            </button>

                            <button
                                type="button"
                                class="zone-toggle-button flex h-7 w-7 items-center justify-center rounded border border-gray-200 text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                data-zone-toggle="{{ $zone->id }}"
                                aria-expanded="false"
                                title="@lang('admin::app.settings.delivery_zones.zones.expand-details')"
                            >
                                ▾
                            </button>
                        </div>

                        <div class="zone-collapsible hidden border-t border-gray-200 p-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300" data-zone-details="{{ $zone->id }}">
                            <p>@lang('admin::app.settings.delivery_zones.edit.code'): {{ $zone->code }}</p>
                            <p>@lang('admin::app.settings.delivery_zones.zones.status'): {{ $zone->is_active ? __('admin::app.settings.delivery_zones.edit.active') : __('admin::app.settings.delivery_zones.edit.inactive') }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded border border-dashed border-gray-300 p-3 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        @lang('admin::app.settings.delivery_zones.zones.empty-zones')
                    </p>
                @endforelse
            </div>
        </div>

        <div class="flex min-w-0 flex-col gap-3.5">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="mb-3 text-base font-semibold text-gray-800 dark:text-white">
                    @lang('admin::app.settings.delivery_zones.zones.zones-map')
                </p>

                <div id="city-zones-map" class="h-[500px] w-full rounded border border-gray-200 dark:border-gray-600"></div>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <label class="flex cursor-pointer items-center">
                        <input id="polygon-edit-mode" type="checkbox" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="ml-2 block text-sm text-gray-900 dark:text-gray-300">@lang('admin::app.settings.delivery_zones.zones.edit-selected-zone')</span>
                    </label>

                    <button type="button" id="clear-polygon" class="secondary-button">
                        @lang('admin::app.settings.delivery_zones.edit.clear-polygon')
                    </button>

                    <button type="button" id="apply-polygon-json" class="secondary-button">
                        @lang('admin::app.settings.delivery_zones.edit.apply-polygon-json')
                    </button>
                </div>

                <p id="polygon-error" class="mt-2 hidden text-sm text-red-600 dark:text-red-400"></p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <form id="zone-form" method="POST" action="{{ route('admin.settings.delivery_zones.store') }}">
                    @csrf

                    <input type="hidden" id="zone-form-method" name="_method" value="PUT" disabled>
                    <input type="hidden" id="city_id" name="city_id" value="{{ $deliveryCity->id }}">
                    <input type="hidden" id="redirect_city_id" name="redirect_city_id" value="{{ $deliveryCity->id }}">
                    <input type="hidden" id="zone_id" name="zone_id" value="">
                    <input type="hidden" name="is_active" value="0">

                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <p id="zone-form-title" class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.settings.delivery_zones.zones.new-zone')
                        </p>

                        <div class="flex items-center gap-2">
                            <button type="button" id="cancel-edit-zone-button" class="secondary-button">
                                @lang('admin::app.settings.delivery_zones.zones.cancel')
                            </button>

                            <button type="button" id="delete-zone-button" class="secondary-button">
                                @lang('admin::app.settings.delivery_zones.zones.delete-zone')
                            </button>

                            <button type="submit" class="primary-button">
                                @lang('admin::app.settings.delivery_zones.zones.save-zone')
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 max-md:grid-cols-1">
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.code')</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" id="code" name="code" rules="required" :value="old('code')" />
                            <x-admin::form.control-group.error control-name="code" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.zone-name')</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="text" id="name" name="name" rules="required" :value="old('name')" />
                            <x-admin::form.control-group.error control-name="name" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>@lang('admin::app.settings.delivery_zones.edit.delivery-time-min')</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="number" id="delivery_time_minutes" name="delivery_time_minutes" :value="old('delivery_time_minutes', 60)" />
                            <x-admin::form.control-group.error control-name="delivery_time_minutes" />
                        </x-admin::form.control-group>

                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.inventory-source')</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="select" id="inventory_source_ids" name="inventory_source_ids" rules="required">
                                <option value="">@lang('admin::app.settings.delivery_zones.edit.select-inventory-source')</option>

                                @foreach ($inventorySources as $source)
                                    <option value="{{ $source->id }}" @selected((int) old('inventory_source_ids') === (int) $source->id)>
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>
                            <x-admin::form.control-group.error control-name="inventory_source_ids" />
                        </x-admin::form.control-group>

                        <div class="col-span-2 grid grid-cols-1 gap-3 sm:grid-cols-3 max-md:col-span-1">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.polygon-color')</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="color" id="polygon_color" name="polygon_color" rules="required" :value="old('polygon_color', '#0077cc')" />
                                <x-admin::form.control-group.error control-name="polygon_color" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.polygon-fill-opacity')</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" id="polygon_fill_opacity" name="polygon_fill_opacity" min="0" max="1" step="0.01" rules="required" :value="old('polygon_fill_opacity', 0.2)" />
                                <x-admin::form.control-group.error control-name="polygon_fill_opacity" />
                            </x-admin::form.control-group>

                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.border-opacity')</x-admin::form.control-group.label>
                                <x-admin::form.control-group.control type="number" id="polygon_stroke_opacity" name="polygon_stroke_opacity" min="0" max="1" step="0.01" rules="required" :value="old('polygon_stroke_opacity', 1)" />
                                <x-admin::form.control-group.error control-name="polygon_stroke_opacity" />
                            </x-admin::form.control-group>
                        </div>

                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>@lang('admin::app.settings.delivery_zones.zones.status')</x-admin::form.control-group.label>
                            <x-admin::form.control-group.control type="switch" id="is_active" name="is_active" value="1" :checked="(bool) old('is_active', 1)" />
                        </x-admin::form.control-group>
                    </div>

                    <div class="mt-3">
                        <p class="mb-2 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.settings.delivery_zones.edit.zone-rates')
                        </p>

                        <div class="mb-2 grid grid-cols-4 gap-2 max-md:grid-cols-1">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">@lang('admin::app.settings.delivery_zones.edit.min-order-total')</p>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">@lang('admin::app.settings.delivery_zones.edit.price')</p>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">@lang('admin::app.settings.delivery_zones.edit.sort-order')</p>
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400"></p>
                        </div>

                        <div id="rates-wrapper" class="space-y-2"></div>

                        <button type="button" id="add-rate-row" class="secondary-button mt-3">
                            @lang('admin::app.settings.delivery_zones.edit.add-rate-row')
                        </button>
                    </div>

                    <x-admin::form.control-group class="mt-3 !mb-0">
                        <x-admin::form.control-group.label class="required">@lang('admin::app.settings.delivery_zones.edit.polygon-json')</x-admin::form.control-group.label>
                        <x-admin::form.control-group.control type="textarea" id="polygon_json" name="polygon_json" rules="required" :value="old('polygon_json', '[]')" />
                        <x-admin::form.control-group.error control-name="polygon_json" />
                    </x-admin::form.control-group>
                </form>
            </div>
        </div>
    </div>

    @php
        $zoneTranslationsForJs = [
            'edit_zone_title' => __('admin::app.settings.delivery_zones.zones.edit-zone-title'),
            'new_zone' => __('admin::app.settings.delivery_zones.zones.new-zone'),
            'delete_zone_confirm' => __('admin::app.settings.delivery_zones.zones.delete-zone-confirm'),
            'invalid_polygon_json' => __('admin::app.settings.delivery_zones.zones.invalid-polygon-json'),
            'polygon_must_be_array' => __('admin::app.settings.delivery_zones.zones.polygon-must-be-array'),
            'unable_to_delete_zone' => __('admin::app.settings.delivery_zones.zones.unable-to-delete-zone'),
            'point_must_be_lat_lng' => __('admin::app.settings.delivery_zones.js.point-must-be-lat-lng'),
            'lat_lng_numeric' => __('admin::app.settings.delivery_zones.js.lat-lng-numeric'),
            'polygon_min_vertices' => __('admin::app.settings.delivery_zones.js.polygon-min-vertices'),
            'remove_rate' => __('admin::app.settings.delivery_zones.edit.remove-rate'),
        ];
    @endphp

    @pushOnce('scripts')
        <script src="{{ $yandexMapsScriptUrl }}"></script>

        <script type="module">
            const zoneTranslations = @json($zoneTranslationsForJs);

            const initCityZonesPage = () => {
                const cityZones = @json($zonesPayload);
                const oldZone = @json($oldZonePayload);
                const cityCenter = [Number({{ (float) ($deliveryCity->center_lat ?? 55.751244) }}), Number({{ (float) ($deliveryCity->center_lng ?? 37.618423) }})];
                const cityPolygon = @json($deliveryCity->polygon_json ?? []);
                const deleteRouteTemplate = @json(route('admin.settings.delivery_zones.delete', '__ZONE_ID__'));
                const updateRouteTemplate = @json(route('admin.settings.delivery_zones.update', '__ZONE_ID__'));
                const createRoute = @json(route('admin.settings.delivery_zones.store'));
                const cityZonesRoute = @json(route('admin.settings.delivery_cities.zones', $deliveryCity->id));

                const MIN_POLYGON_VERTEX_COUNT = 3;
                const zonesMap = new Map(cityZones.map((zone) => [zone.id, zone]));

                const zoneForm = document.getElementById('zone-form');
                const zoneFormMethod = document.getElementById('zone-form-method');
                const zoneFormTitle = document.getElementById('zone-form-title');
                const zoneIdInput = document.getElementById('zone_id');
                const codeInput = document.getElementById('code');
                const nameInput = document.getElementById('name');
                const deliveryTimeInput = document.getElementById('delivery_time_minutes');
                const inventorySourceInput = document.getElementById('inventory_source_ids');
                const polygonInput = document.getElementById('polygon_json');
                const polygonColorInput = document.getElementById('polygon_color');
                const polygonFillOpacityInput = document.getElementById('polygon_fill_opacity');
                const polygonStrokeOpacityInput = document.getElementById('polygon_stroke_opacity');
                const isActiveInput = document.getElementById('is_active');
                const addZoneButton = document.getElementById('add-zone-button');
                const cancelEditZoneButton = document.getElementById('cancel-edit-zone-button');
                const deleteZoneButton = document.getElementById('delete-zone-button');
                const addRateRowButton = document.getElementById('add-rate-row');
                const ratesWrapper = document.getElementById('rates-wrapper');
                const clearPolygonButton = document.getElementById('clear-polygon');
                const applyPolygonJsonButton = document.getElementById('apply-polygon-json');
                const editModeInput = document.getElementById('polygon-edit-mode');
                const polygonError = document.getElementById('polygon-error');

                if (
                    ! (zoneForm instanceof HTMLFormElement)
                    || ! (zoneFormMethod instanceof HTMLInputElement)
                    || ! (zoneFormTitle instanceof HTMLElement)
                    || ! (zoneIdInput instanceof HTMLInputElement)
                    || ! (codeInput instanceof HTMLInputElement)
                    || ! (nameInput instanceof HTMLInputElement)
                    || ! (deliveryTimeInput instanceof HTMLInputElement)
                    || ! (inventorySourceInput instanceof HTMLSelectElement)
                    || ! (polygonInput instanceof HTMLTextAreaElement)
                    || ! (polygonColorInput instanceof HTMLInputElement)
                    || ! (polygonFillOpacityInput instanceof HTMLInputElement)
                    || ! (polygonStrokeOpacityInput instanceof HTMLInputElement)
                    || ! (isActiveInput instanceof HTMLInputElement)
                    || ! ratesWrapper
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

                const normalizePoint = (value) => {
                    if (! Array.isArray(value) || value.length < 2) {
                        throw new Error(zoneTranslations.point_must_be_lat_lng);
                    }

                    const latitude = Number(value[0]);
                    const longitude = Number(value[1]);

                    if (! Number.isFinite(latitude) || ! Number.isFinite(longitude)) {
                        throw new Error(zoneTranslations.lat_lng_numeric);
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

                const parseCoordinatesValue = (value) => {
                    if (Array.isArray(value)) {
                        if (! value.length) {
                            return [];
                        }

                        let rawCoordinates = value;

                        if (Array.isArray(value[0]) && value[0].length && Array.isArray(value[0][0])) {
                            rawCoordinates = value[0];
                        }

                        return stripClosingPoint(rawCoordinates.map(normalizePoint));
                    }

                    let parsed;

                    try {
                        parsed = JSON.parse(String(value || '[]'));
                    } catch (error) {
                        throw new Error(zoneTranslations.invalid_polygon_json);
                    }

                    if (! Array.isArray(parsed)) {
                        throw new Error(zoneTranslations.polygon_must_be_array);
                    }

                    return parseCoordinatesValue(parsed);
                };

                const normalizePolygonColor = (value) => {
                    const normalized = String(value || '').trim().toLowerCase();

                    if (! /^#[0-9a-f]{6}$/.test(normalized)) {
                        return '#0077cc';
                    }

                    return normalized;
                };

                const normalizeOpacity = (value, fallback) => {
                    const numeric = Number.parseFloat(String(value));

                    if (! Number.isFinite(numeric)) {
                        return fallback;
                    }

                    return Math.min(1, Math.max(0, numeric));
                };

                const hexToRgb = (hexColor) => {
                    const normalizedColor = normalizePolygonColor(hexColor);
                    const value = normalizedColor.replace('#', '');

                    return {
                        red: Number.parseInt(value.slice(0, 2), 16),
                        green: Number.parseInt(value.slice(2, 4), 16),
                        blue: Number.parseInt(value.slice(4, 6), 16),
                    };
                };

                const toRgba = (hexColor, opacity) => {
                    const rgb = hexToRgb(hexColor);

                    return `rgba(${rgb.red}, ${rgb.green}, ${rgb.blue}, ${normalizeOpacity(opacity, 1)})`;
                };

                const emptyZoneData = () => ({
                    id: null,
                    city_id: Number(document.getElementById('city_id')?.value || 0),
                    code: '',
                    name: '',
                    polygon_json: [],
                    polygon_color: '#0077cc',
                    polygon_fill_opacity: 0.2,
                    polygon_stroke_opacity: 1,
                    delivery_time_minutes: 60,
                    is_active: true,
                    inventory_source_id: Number(inventorySourceInput.value || 0),
                    rates: [
                        {
                            min_order_total: 0,
                            price: 0,
                            sort_order: 0,
                        },
                    ],
                });

                const normalizeZone = (zoneData) => {
                    const coordinates = parseCoordinatesValue(zoneData?.polygon_json ?? []);
                    const normalizedRates = Array.isArray(zoneData?.rates) && zoneData.rates.length
                        ? zoneData.rates.map((rate, index) => ({
                            min_order_total: Number(rate?.min_order_total ?? 0),
                            price: Number(rate?.price ?? 0),
                            sort_order: Number(rate?.sort_order ?? index),
                        }))
                        : [
                            {
                                min_order_total: 0,
                                price: 0,
                                sort_order: 0,
                            },
                        ];

                    return {
                        id: zoneData?.id ? Number(zoneData.id) : null,
                        city_id: zoneData?.city_id ? Number(zoneData.city_id) : Number(document.getElementById('city_id')?.value || 0),
                        code: String(zoneData?.code ?? ''),
                        name: String(zoneData?.name ?? ''),
                        polygon_json: coordinates,
                        polygon_color: normalizePolygonColor(zoneData?.polygon_color ?? '#0077cc'),
                        polygon_fill_opacity: normalizeOpacity(zoneData?.polygon_fill_opacity, 0.2),
                        polygon_stroke_opacity: normalizeOpacity(zoneData?.polygon_stroke_opacity, 1),
                        delivery_time_minutes: zoneData?.delivery_time_minutes === null || zoneData?.delivery_time_minutes === undefined
                            ? 60
                            : Number(zoneData.delivery_time_minutes),
                        is_active: Boolean(zoneData?.is_active),
                        inventory_source_id: Number(zoneData?.inventory_source_id ?? 0),
                        rates: normalizedRates,
                    };
                };

                const normalizeOldZone = (zoneData) => {
                    if (! zoneData) {
                        return null;
                    }

                    return normalizeZone({
                        ...zoneData,
                        polygon_json: zoneData.polygon_json,
                    });
                };

                const allZones = cityZones.map((zone) => normalizeZone(zone));
                allZones.forEach((zone) => zonesMap.set(zone.id, zone));

                const oldZoneData = normalizeOldZone(oldZone);
                let currentZone = oldZoneData ?? (allZones.length ? { ...allZones[0] } : emptyZoneData());
                let selectedZoneId = oldZoneData?.id ?? (allZones.length ? allZones[0].id : null);
                let editMode = ! (editModeInput instanceof HTMLInputElement) || editModeInput.checked;
                let map = null;
                let cityPolygonObject = null;
                let zonePolygonObjects = new Map();
                let draftPolygonObject = null;
                let suppressPolygonSync = false;

                const inputClasses = 'w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';

                const renderRates = (rates) => {
                    ratesWrapper.innerHTML = '';

                    rates.forEach((rate, index) => {
                        const row = document.createElement('div');
                        row.className = 'grid grid-cols-4 gap-2 max-md:grid-cols-1';
                        row.innerHTML = `
                            <div>
                                <input class="${inputClasses}" type="number" step="0.01" name="rates[${index}][min_order_total]" value="${Number(rate.min_order_total)}">
                            </div>
                            <div>
                                <input class="${inputClasses}" type="number" step="0.01" name="rates[${index}][price]" value="${Number(rate.price)}">
                            </div>
                            <div>
                                <input class="${inputClasses}" type="number" name="rates[${index}][sort_order]" value="${Number(rate.sort_order)}">
                            </div>
                            <div class="flex items-end">
                                <button type="button" class="secondary-button remove-rate">${zoneTranslations.remove_rate}</button>
                            </div>
                        `;

                        ratesWrapper.appendChild(row);
                    });
                };

                const getRatesFromDom = () => {
                    const rows = Array.from(ratesWrapper.querySelectorAll('.grid'));

                    if (! rows.length) {
                        return [
                            {
                                min_order_total: 0,
                                price: 0,
                                sort_order: 0,
                            },
                        ];
                    }

                    return rows.map((row, index) => {
                        const minOrderInput = row.querySelector(`input[name="rates[${index}][min_order_total]"]`);
                        const priceInput = row.querySelector(`input[name="rates[${index}][price]"]`);
                        const sortOrderInput = row.querySelector(`input[name="rates[${index}][sort_order]"]`);

                        return {
                            min_order_total: Number((minOrderInput instanceof HTMLInputElement ? minOrderInput.value : 0) || 0),
                            price: Number((priceInput instanceof HTMLInputElement ? priceInput.value : 0) || 0),
                            sort_order: Number((sortOrderInput instanceof HTMLInputElement ? sortOrderInput.value : index) || index),
                        };
                    });
                };

                const setFormMode = (mode) => {
                    if (mode === 'update' && currentZone.id) {
                        zoneForm.action = updateRouteTemplate.replace('__ZONE_ID__', String(currentZone.id));
                        zoneFormMethod.disabled = false;
                        zoneFormMethod.value = 'PUT';
                        zoneFormTitle.textContent = zoneTranslations.edit_zone_title.replace(':name', currentZone.name || currentZone.code || currentZone.id);
                        deleteZoneButton?.classList.remove('hidden');
                    } else {
                        zoneForm.action = createRoute;
                        zoneFormMethod.disabled = true;
                        zoneFormMethod.value = 'PUT';
                        zoneFormTitle.textContent = zoneTranslations.new_zone;
                        deleteZoneButton?.classList.add('hidden');
                    }
                };

                const syncFormFromCurrentZone = () => {
                    zoneIdInput.value = currentZone.id ? String(currentZone.id) : '';
                    codeInput.value = currentZone.code || '';
                    nameInput.value = currentZone.name || '';
                    deliveryTimeInput.value = currentZone.delivery_time_minutes === null ? '' : String(currentZone.delivery_time_minutes);
                    inventorySourceInput.value = currentZone.inventory_source_id ? String(currentZone.inventory_source_id) : '';
                    polygonInput.value = JSON.stringify(currentZone.polygon_json || []);
                    polygonColorInput.value = normalizePolygonColor(currentZone.polygon_color || '#0077cc');
                    polygonFillOpacityInput.value = normalizeOpacity(currentZone.polygon_fill_opacity, 0.2).toFixed(2);
                    polygonStrokeOpacityInput.value = normalizeOpacity(currentZone.polygon_stroke_opacity, 1).toFixed(2);
                    isActiveInput.checked = Boolean(currentZone.is_active);
                    renderRates(currentZone.rates || []);
                    setFormMode(currentZone.id ? 'update' : 'create');
                    showPolygonError('');
                };

                const syncCurrentZoneFromForm = () => {
                    currentZone = normalizeZone({
                        ...currentZone,
                        id: zoneIdInput.value ? Number(zoneIdInput.value) : null,
                        code: codeInput.value,
                        name: nameInput.value,
                        delivery_time_minutes: deliveryTimeInput.value === '' ? null : Number(deliveryTimeInput.value),
                        inventory_source_id: inventorySourceInput.value ? Number(inventorySourceInput.value) : 0,
                        polygon_json: polygonInput.value,
                        polygon_color: polygonColorInput.value,
                        polygon_fill_opacity: polygonFillOpacityInput.value,
                        polygon_stroke_opacity: polygonStrokeOpacityInput.value,
                        is_active: isActiveInput.checked,
                        rates: getRatesFromDom(),
                    });
                };

                const updateZoneItemState = () => {
                    document.querySelectorAll('[data-zone-item]').forEach((element) => {
                        if (! (element instanceof HTMLElement)) {
                            return;
                        }

                        const zoneId = Number(element.dataset.zoneItem);

                        if (zoneId === selectedZoneId) {
                            element.classList.add('ring-1', 'ring-blue-500');
                        } else {
                            element.classList.remove('ring-1', 'ring-blue-500');
                        }
                    });
                };

                const zoomToCoordinates = (coordinates) => {
                    if (! map || ! coordinates.length) {
                        return;
                    }

                    const polygon = new ymaps.Polygon([coordinates], {}, {});
                    const bounds = polygon.geometry.getBounds();

                    if (bounds) {
                        map.setBounds(bounds, { checkZoomRange: true, duration: 150 });
                    }
                };

                const applyPolygonObjectStyle = (polygonObject, zone, isSelected) => {
                    polygonObject.options.set({
                        fillColor: toRgba(zone.polygon_color, zone.polygon_fill_opacity),
                        strokeColor: toRgba(zone.polygon_color, zone.polygon_stroke_opacity),
                        strokeWidth: isSelected ? 5 : 3,
                        fillOpacity: 1,
                    });
                };

                const removeAllZonePolygons = () => {
                    zonePolygonObjects.forEach((polygonObject) => {
                        map?.geoObjects.remove(polygonObject);
                    });

                    zonePolygonObjects = new Map();
                };

                const renderDraftPolygon = () => {
                    if (! map) {
                        return;
                    }

                    if (draftPolygonObject) {
                        map.geoObjects.remove(draftPolygonObject);
                        draftPolygonObject = null;
                    }

                    if (selectedZoneId || ! Array.isArray(currentZone.polygon_json) || currentZone.polygon_json.length < MIN_POLYGON_VERTEX_COUNT) {
                        return;
                    }

                    draftPolygonObject = new ymaps.Polygon([currentZone.polygon_json], {}, {});
                    applyPolygonObjectStyle(draftPolygonObject, currentZone, true);
                    map.geoObjects.add(draftPolygonObject);

                    if (editMode) {
                        draftPolygonObject.editor.startEditing();
                    }

                    draftPolygonObject.geometry.events.add('change', () => {
                        if (suppressPolygonSync) {
                            return;
                        }

                        const geometryCoordinates = draftPolygonObject.geometry.getCoordinates();

                        if (! Array.isArray(geometryCoordinates) || ! Array.isArray(geometryCoordinates[0])) {
                            return;
                        }

                        const normalizedCoordinates = stripClosingPoint(geometryCoordinates[0].map(normalizePoint));
                        currentZone.polygon_json = normalizedCoordinates;
                        polygonInput.value = JSON.stringify(normalizedCoordinates);
                    });
                };

                const renderStoredZonePolygons = () => {
                    if (! map) {
                        return;
                    }

                    removeAllZonePolygons();

                    const zonesToRender = Array.from(zonesMap.values());

                    zonesToRender.forEach((zone) => {
                        let zoneToRender = zone;

                        if (selectedZoneId && zone.id === selectedZoneId) {
                            zoneToRender = { ...zone, ...currentZone, id: zone.id };
                        }

                        if (! Array.isArray(zoneToRender.polygon_json) || zoneToRender.polygon_json.length < MIN_POLYGON_VERTEX_COUNT) {
                            return;
                        }

                        const polygonObject = new ymaps.Polygon([zoneToRender.polygon_json], {}, {});
                        const isSelected = selectedZoneId === zone.id;
                        applyPolygonObjectStyle(polygonObject, zoneToRender, isSelected);

                        polygonObject.geometry.events.add('change', () => {
                            if (suppressPolygonSync || selectedZoneId !== zone.id) {
                                return;
                            }

                            const geometryCoordinates = polygonObject.geometry.getCoordinates();

                            if (! Array.isArray(geometryCoordinates) || ! Array.isArray(geometryCoordinates[0])) {
                                return;
                            }

                            const normalizedCoordinates = stripClosingPoint(geometryCoordinates[0].map(normalizePoint));
                            currentZone.polygon_json = normalizedCoordinates;
                            polygonInput.value = JSON.stringify(normalizedCoordinates);
                        });

                        zonePolygonObjects.set(zone.id, polygonObject);
                        map.geoObjects.add(polygonObject);

                        if (isSelected && editMode) {
                            polygonObject.editor.startEditing();
                        }
                    });
                };

                const renderMap = () => {
                    if (! map) {
                        return;
                    }

                    suppressPolygonSync = true;
                    renderStoredZonePolygons();
                    renderDraftPolygon();
                    suppressPolygonSync = false;
                };

                const selectZone = (zoneId) => {
                    const zone = zonesMap.get(zoneId);

                    if (! zone) {
                        return;
                    }

                    selectedZoneId = zoneId;
                    currentZone = normalizeZone(zone);
                    syncFormFromCurrentZone();
                    updateZoneItemState();
                    renderMap();
                    zoomToCoordinates(currentZone.polygon_json);
                };

                const startNewZone = () => {
                    selectedZoneId = null;
                    currentZone = emptyZoneData();
                    syncFormFromCurrentZone();
                    updateZoneItemState();
                    renderMap();
                };

                const refreshSelectedZoneInMapData = () => {
                    if (! selectedZoneId) {
                        return;
                    }

                    zonesMap.set(selectedZoneId, normalizeZone({
                        ...zonesMap.get(selectedZoneId),
                        ...currentZone,
                        id: selectedZoneId,
                    }));
                };

                const syncCurrentZoneAndRender = () => {
                    try {
                        syncCurrentZoneFromForm();
                        showPolygonError('');
                    } catch (error) {
                        showPolygonError(error.message);
                    }

                    refreshSelectedZoneInMapData();
                    renderMap();
                };

                const updateEditMode = () => {
                    if (! map) {
                        return;
                    }

                    zonePolygonObjects.forEach((polygonObject, zoneId) => {
                        if (zoneId !== selectedZoneId) {
                            polygonObject.editor.stopEditing();

                            return;
                        }

                        if (editMode) {
                            polygonObject.editor.startEditing();
                        } else {
                            polygonObject.editor.stopEditing();
                        }
                    });

                    if (draftPolygonObject) {
                        if (editMode) {
                            draftPolygonObject.editor.startEditing();
                        } else {
                            draftPolygonObject.editor.stopEditing();
                        }
                    }
                };

                addZoneButton?.addEventListener('click', () => {
                    startNewZone();
                });

                cancelEditZoneButton?.addEventListener('click', () => {
                    if (selectedZoneId && zonesMap.has(selectedZoneId)) {
                        selectZone(selectedZoneId);

                        return;
                    }

                    startNewZone();
                });

                document.querySelectorAll('.zone-select-button').forEach((buttonElement) => {
                    buttonElement.addEventListener('click', () => {
                        if (! (buttonElement instanceof HTMLElement)) {
                            return;
                        }

                        const zoneId = Number(buttonElement.dataset.zoneId);
                        selectZone(zoneId);
                    });
                });

                document.querySelectorAll('.zone-toggle-button').forEach((buttonElement) => {
                    buttonElement.addEventListener('click', () => {
                        if (! (buttonElement instanceof HTMLElement)) {
                            return;
                        }

                        const zoneId = Number(buttonElement.dataset.zoneToggle);
                        const details = document.querySelector(`[data-zone-details="${zoneId}"]`);

                        if (! (details instanceof HTMLElement)) {
                            return;
                        }

                        details.classList.toggle('hidden');

                        const expanded = ! details.classList.contains('hidden');
                        buttonElement.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                        buttonElement.textContent = expanded ? '▴' : '▾';
                    });
                });

                addRateRowButton?.addEventListener('click', () => {
                    const currentRates = getRatesFromDom();
                    currentRates.push({
                        min_order_total: 0,
                        price: 0,
                        sort_order: currentRates.length,
                    });
                    renderRates(currentRates);
                    syncCurrentZoneAndRender();
                });

                ratesWrapper.addEventListener('click', (event) => {
                    const target = event.target;

                    if (! (target instanceof HTMLElement) || ! target.classList.contains('remove-rate')) {
                        return;
                    }

                    const rows = Array.from(ratesWrapper.querySelectorAll('.grid'));
                    const targetRow = target.closest('.grid');

                    if (! targetRow || rows.length <= 1) {
                        return;
                    }

                    targetRow.remove();

                    const updatedRates = getRatesFromDom().map((rate, index) => ({
                        ...rate,
                        sort_order: index,
                    }));

                    renderRates(updatedRates);
                    syncCurrentZoneAndRender();
                });

                ratesWrapper.addEventListener('input', () => {
                    syncCurrentZoneAndRender();
                });

                [codeInput, nameInput, deliveryTimeInput, inventorySourceInput, polygonColorInput, polygonFillOpacityInput, polygonStrokeOpacityInput, isActiveInput, polygonInput].forEach((element) => {
                    element.addEventListener('input', () => {
                        syncCurrentZoneAndRender();
                    });
                });

                clearPolygonButton?.addEventListener('click', () => {
                    currentZone.polygon_json = [];
                    polygonInput.value = '[]';
                    syncCurrentZoneAndRender();
                });

                applyPolygonJsonButton?.addEventListener('click', () => {
                    try {
                        currentZone.polygon_json = parseCoordinatesValue(polygonInput.value);
                        polygonInput.value = JSON.stringify(currentZone.polygon_json);
                        showPolygonError('');
                        syncCurrentZoneAndRender();
                    } catch (error) {
                        showPolygonError(error.message);
                    }
                });

                editModeInput?.addEventListener('change', () => {
                    if (! (editModeInput instanceof HTMLInputElement)) {
                        return;
                    }

                    editMode = editModeInput.checked;
                    updateEditMode();
                });

                deleteZoneButton?.addEventListener('click', async () => {
                    if (! selectedZoneId) {
                        return;
                    }

                    if (! window.confirm(zoneTranslations.delete_zone_confirm)) {
                        return;
                    }

                    const tokenInput = zoneForm.querySelector('input[name="_token"]');

                    if (! (tokenInput instanceof HTMLInputElement)) {
                        return;
                    }

                    const deleteUrl = deleteRouteTemplate.replace('__ZONE_ID__', String(selectedZoneId));
                    const formData = new FormData();
                    formData.append('_token', tokenInput.value);
                    formData.append('_method', 'DELETE');

                    try {
                        const response = await fetch(deleteUrl, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (! response.ok) {
                            throw new Error(zoneTranslations.unable_to_delete_zone);
                        }

                        window.location.href = cityZonesRoute;
                    } catch (error) {
                        window.alert(error.message || zoneTranslations.unable_to_delete_zone);
                    }
                });

                if (typeof ymaps === 'undefined' || typeof ymaps.ready !== 'function') {
                    return;
                }

                ymaps.ready(() => {
                    map = new ymaps.Map('city-zones-map', {
                        center: cityCenter,
                        zoom: 10,
                    });
                    map.behaviors.disable('dblClickZoom');

                    if (Array.isArray(cityPolygon) && cityPolygon.length >= MIN_POLYGON_VERTEX_COUNT) {
                        cityPolygonObject = new ymaps.Polygon([parseCoordinatesValue(cityPolygon)], {}, {
                            fillColor: 'rgba(128, 128, 128, 0.1)',
                            strokeColor: 'rgba(128, 128, 128, 0.5)',
                            strokeWidth: 2,
                            interactivityModel: 'default#silent',
                        });

                        map.geoObjects.add(cityPolygonObject);
                    }

                    syncFormFromCurrentZone();
                    updateZoneItemState();
                    renderMap();

                    if (selectedZoneId) {
                        zoomToCoordinates(currentZone.polygon_json);
                    }

                    map.events.add('dblclick', (event) => {
                        if (! editMode) {
                            return;
                        }

                        const point = event.get('coords').map((coordinate) => Number(coordinate.toFixed(7)));
                        const nextCoordinates = Array.isArray(currentZone.polygon_json)
                            ? [...currentZone.polygon_json, point]
                            : [point];

                        currentZone.polygon_json = nextCoordinates;
                        polygonInput.value = JSON.stringify(nextCoordinates);
                        syncCurrentZoneAndRender();
                    });
                });
            };

            window.addEventListener('load', () => {
                window.setTimeout(initCityZonesPage, 0);
            });
        </script>
    @endpushOnce
</x-admin::layouts>
