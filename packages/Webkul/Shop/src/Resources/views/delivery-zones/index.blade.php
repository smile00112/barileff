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
                const zonesApiUrl = @json(route('shop.api.delivery_zones.index'));
                const selectApiUrl = @json(route('shop.api.delivery_zones.select'));
                const zoneSelectMessage = document.getElementById('zone-select-message');

                if (!mapEl) return;

                const showMessage = (text, isError = false) => {
                    if (!zoneSelectMessage) return;
                    zoneSelectMessage.textContent = text;
                    zoneSelectMessage.classList.remove('hidden');
                    zoneSelectMessage.classList.toggle('text-red-600', isError);
                    zoneSelectMessage.classList.toggle('text-zinc-600', !isError);
                };

                ymaps.ready(function() {
                    const defaultCenter = [55.751244, 37.618423];
                    const map = new ymaps.Map('shop-delivery-zones-map', {
                        center: defaultCenter,
                        zoom: 10,
                        controls: ['zoomControl', 'fullscreenControl']
                    });

                    fetch(zonesApiUrl)
                        .then(r => r.json())
                        .then(response => {
                            const cities = response.data || [];
                            let bounds = null;

                            cities.forEach(city => {
                                const cityCenter = [Number(city.center_lat) || defaultCenter[0], Number(city.center_lng) || defaultCenter[1]];
                                if (city.center_lat && city.center_lng) {
                                    if (!bounds) bounds = [cityCenter.slice(), cityCenter.slice()];
                                    bounds[0][0] = Math.min(bounds[0][0], cityCenter[0]);
                                    bounds[0][1] = Math.min(bounds[0][1], cityCenter[1]);
                                    bounds[1][0] = Math.max(bounds[1][0], cityCenter[0]);
                                    bounds[1][1] = Math.max(bounds[1][1], cityCenter[1]);
                                }

                                (city.zones || []).forEach(zone => {
                                    const coords = (zone.polygon_json || []).map(p => [Number(p[0]) || 0, Number(p[1]) || 0]).filter(p => p[0] && p[1]);
                                    if (coords.length < 3) return;

                                    const fillColor = (zone.polygon_color || '#0077cc') + '33';
                                    const polygon = new ymaps.Polygon([coords], {}, {
                                        fillColor: fillColor,
                                        strokeColor: zone.polygon_color || '#0077cc',
                                        strokeWidth: 2,
                                        cursor: 'pointer'
                                    });

                                    polygon.events.add('click', function() {
                                        const formData = new FormData();
                                        formData.append('delivery_zone_id', zone.id);
                                        formData.append('_token', @json(csrf_token()));

                                        fetch(selectApiUrl, {
                                            method: 'POST',
                                            headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest'
                                            },
                                            body: formData
                                        })
                                        .then(r => r.json())
                                        .then(data => {
                                            if (data.data?.zone) {
                                                showMessage(zone.name + ' — selected');
                                                setTimeout(() => window.location.href = @json(route('shop.home.index')), 800);
                                            } else if (data.data?.message) {
                                                showMessage(data.data.message, true);
                                            }
                                        })
                                        .catch(() => showMessage('Error selecting zone', true));
                                    });

                                    map.geoObjects.add(polygon);
                                });
                            });

                            if (bounds) {
                                map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 50 });
                            }
                        })
                        .catch(() => showMessage('Unable to load delivery zones', true));
                });
            });
        </script>
    @endPushOnce
</x-shop::layouts>
