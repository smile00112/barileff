<?php

namespace Webkul\DeliveryZones\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Webkul\Checkout\Facades\Cart;
use Webkul\DeliveryZones\Models\DeliveryCity;
use Webkul\DeliveryZones\Services\ZoneSelector;
use Webkul\Inventory\Models\InventorySource;

/**
 * Public API for delivery zones map on storefront.
 *
 * @group Зоны доставки
 */
class DeliveryZonesController
{
    /**
     * Получить города и зоны доставки.
     *
     * Возвращает список городов с зонами, привязанными к inventory sources текущего канала.
     * Используется для отображения карты доставки.
     *
     * @response 200 scenario="Есть зоны" {"data":{"data":[{"id":1,"name":"Москва","center_lat":55.7558,"center_lng":37.6173,"polygon_json":[[55.7,37.5],[55.8,37.5],[55.8,37.7]],"zones":[{"id":1,"name":"Центр","polygon_json":[[55.75,37.61],[55.76,37.62]],"polygon_color":"#0077cc","inventory_source_id":1,"rates":[{"min_order_total":0,"price":300,"sort_order":1},{"min_order_total":2000,"price":0,"sort_order":2}]}]}]}}
     * @response 200 scenario="Нет зон" {"data":{"data":[]}}
     *
     * @responseField data.*.id integer ID города.
     * @responseField data.*.name string Название города.
     * @responseField data.*.center_lat number Широта центра города.
     * @responseField data.*.center_lng number Долгота центра города.
     * @responseField data.*.polygon_json array Полигон границы города (массив координат [lat, lng]).
     * @responseField data.*.zones object[] Зоны доставки города.
     * @responseField data.*.zones.*.id integer ID зоны.
     * @responseField data.*.zones.*.name string Название зоны.
     * @responseField data.*.zones.*.polygon_json array Полигон зоны (массив координат [lat, lng]).
     * @responseField data.*.zones.*.polygon_color string Цвет полигона (hex).
     * @responseField data.*.zones.*.inventory_source_id integer ID привязанного склада.
     * @responseField data.*.zones.*.rates object[] Тарифы доставки (сортированы по min_order_total desc).
     * @responseField data.*.zones.*.rates.*.min_order_total number Минимальная сумма заказа для тарифа.
     * @responseField data.*.zones.*.rates.*.price number Стоимость доставки.
     * @responseField data.*.zones.*.rates.*.sort_order integer Порядок сортировки.
     */
    public function index(): JsonResource
    {
        $channel = core()->getCurrentChannel();
        $sourceIds = $channel->inventory_sources->pluck('id')->all();

        if (empty($sourceIds)) {
            return new JsonResource(['data' => []]);
        }

        $cities = DeliveryCity::query()
            ->with(['zones' => function ($query) use ($sourceIds) {
                $query->with(['inventory_sources', 'rates'])
                    ->where('is_active', true)
                    ->whereHas('inventory_sources', function ($q) use ($sourceIds) {
                        $q->whereIn('inventory_sources.id', $sourceIds);
                    });
            }])
            ->where('is_active', true)
            ->whereHas('zones', function ($query) use ($sourceIds) {
                $query->where('is_active', true)
                    ->whereHas('inventory_sources', function ($q) use ($sourceIds) {
                        $q->whereIn('inventory_sources.id', $sourceIds);
                    });
            })
            ->orderBy('name')
            ->get();

        $data = $cities->map(function (DeliveryCity $city) {
            return [
                'id' => $city->id,
                'name' => $city->name,
                'center_lat' => $city->center_lat !== null ? (float) $city->center_lat : null,
                'center_lng' => $city->center_lng !== null ? (float) $city->center_lng : null,
                'polygon_json' => $city->polygon_json ?? [],
                'country' => (string) ($city->country ?? ''),
                'state' => (string) ($city->state ?? ''),
                'zones' => $city->zones->map(function ($zone) use ($city) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'polygon_json' => $zone->polygon_json ?? [],
                        'polygon_color' => (string) ($zone->polygon_color ?? '#0077cc'),
                        'polygon_fill_opacity' => (float) ($zone->polygon_fill_opacity ?? 0.2),
                        'polygon_stroke_opacity' => (float) ($zone->polygon_stroke_opacity ?? 1.0),
                        'delivery_time_minutes' => $zone->delivery_time_minutes ? (int) $zone->delivery_time_minutes : null,
                        'inventory_source_id' => (int) $zone->inventory_sources->first()?->id,
                        'city_name' => $city->name,
                        'country' => (string) ($city->country ?? ''),
                        'state' => (string) ($city->state ?? ''),
                        'rates' => $zone->rates()
                            ->orderByDesc('min_order_total')
                            ->orderByDesc('sort_order')
                            ->get()
                            ->map(fn ($rate) => [
                                'min_order_total' => (float) $rate->min_order_total,
                                'price' => (float) $rate->price,
                                'sort_order' => (int) $rate->sort_order,
                            ])
                            ->values()
                            ->all(),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return new JsonResource(['data' => $data]);
    }

    /**
     * Выбрать зону доставки.
     *
     * Выбор зоны по ID или координатам (клик на карте / адрес). Обновляет корзину и сессию.
     * Если корзина существует, возвращает обновлённую корзину и доступные способы доставки.
     *
     * @bodyParam delivery_zone_id integer Явный ID зоны (для ручного выбора). Example: 1
     * @bodyParam delivery_point_lat number Широта точки доставки (-90..90). Example: 55.7558
     * @bodyParam delivery_point_lng number Долгота точки доставки (-180..180). Example: 37.6173
     * @bodyParam city string Название города. Example: Москва
     * @bodyParam shipping_method string Метод доставки. Допустимое значение: `delivery_zones_delivery_zones`. Example: delivery_zones_delivery_zones
     *
     * @response 200 scenario="Зона найдена (есть корзина)" {"data":{"inventory_source_id":1,"zone":{"id":1,"name":"Центр"},"cart":{"id":1,"is_guest":1,"customer_id":null,"items_count":1,"items_qty":1,"delivery_zone":{"id":1,"name":"Центр","mode":"manual","delivery_time_minutes":60},"grand_total":"1 300,00 ₽"},"shipping_methods":[]}}
     * @response 200 scenario="Зона найдена (без корзины)" {"data":{"inventory_source_id":1,"zone":{"id":1,"name":"Центр"}}}
     * @response 422 scenario="Зона не найдена" {"data":{"inventory_source_id":null,"zone":null,"message":"Зона доставки не найдена."}}
     *
     * @responseField data.inventory_source_id integer|null ID склада привязанного к зоне (null если зона не найдена).
     * @responseField data.zone object|null Объект выбранной зоны.
     * @responseField data.zone.id integer ID зоны.
     * @responseField data.zone.name string Название зоны.
     * @responseField data.cart object|null Обновлённая корзина (CartResource), только если корзина существует.
     * @responseField data.shipping_methods array Доступные способы доставки.
     * @responseField data.message string Сообщение об ошибке (только при 422).
     */
    public function select(): JsonResource|JsonResponse
    {
        $validated = request()->validate([
            'delivery_zone_id' => 'nullable|integer|exists:delivery_zones,id',
            'delivery_point_lat' => 'nullable|numeric|between:-90,90',
            'delivery_point_lng' => 'nullable|numeric|between:-180,180',
            'city' => 'nullable|string|max:255',
            'shipping_method' => 'nullable|string|in:delivery_zones_delivery_zones',
        ]);

        $zoneId = ! empty($validated['delivery_zone_id']) ? (int) $validated['delivery_zone_id'] : null;
        $lat = isset($validated['delivery_point_lat']) ? (float) $validated['delivery_point_lat'] : null;
        $lng = isset($validated['delivery_point_lng']) ? (float) $validated['delivery_point_lng'] : null;
        $city = $validated['city'] ?? null;

        $zone = app(ZoneSelector::class)->resolveZoneBySelection($zoneId, $lat, $lng, $city);

        if (! $zone) {
            session()->forget('selected_inventory_source_id');
            $cart = Cart::getCart();
            if ($cart) {
                $cart->inventory_source_id = null;
                $cart->save();
            }

            return response()->json([
                'data' => [
                    'inventory_source_id' => null,
                    'zone' => null,
                    'message' => __('shop::app.delivery-zones.zone-not-found'),
                ],
            ], 422);
        }

        $inventorySourceId = (int) $zone->inventory_sources->first()?->id;
        session(['selected_inventory_source_id' => $inventorySourceId]);
        session(['selected_delivery_zone_id' => $zone->id]);

        $cart = Cart::getCart();

        if ($cart) {
            app(\Webkul\DeliveryZones\Services\CartDeliveryZoneManager::class)->applySelection(
                $cart,
                $lat,
                $lng,
                $zone->id
            );
        }

        return new JsonResource([
            'data' => [
                'inventory_source_id' => $inventorySourceId,
                'zone' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                ],
            ],
        ]);
    }

    /**
     * Получить точки самовывоза.
     *
     * Возвращает активные inventory sources текущего канала с адресами и координатами.
     *
     * @response 200 {"data":[{"id":1,"name":"Склад Центр","street":"ул. Ленина, 1","city":"Москва","state":"МО","country":"RU","postcode":"101000","latitude":55.7558,"longitude":37.6173,"contact_number":"+7 999 123-45-67"}]}
     */
    public function pickupPoints(): JsonResource
    {
        $channel = core()->getCurrentChannel();
        $sourceIds = $channel->inventory_sources->pluck('id')->all();

        if (empty($sourceIds)) {
            return new JsonResource([]);
        }

        $sources = InventorySource::query()
            ->whereIn('id', $sourceIds)
            ->where('status', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('priority')
            ->get();

        $data = $sources->map(fn (InventorySource $source) => [
            'id' => $source->id,
            'name' => $source->name,
            'street' => $source->street,
            'city' => $source->city,
            'state' => $source->state,
            'country' => $source->country,
            'postcode' => $source->postcode,
            'latitude' => (float) $source->latitude,
            'longitude' => (float) $source->longitude,
            'contact_number' => $source->contact_number,
        ])->values()->all();

        return new JsonResource($data);
    }

    /**
     * Определить город по IP-адресу.
     *
     * Использует torann/geoip для определения города из IP запроса.
     * Результат кешируется на 1 час. Возвращает найденный город из базы зон доставки.
     *
     * @response 200 scenario="Город найден" {"data":{"city":"Казань","matched_city_id":3,"matched_city_name":"Казань"}}
     * @response 200 scenario="Город не определён" {"data":{"city":null,"matched_city_id":null,"matched_city_name":null}}
     *
     * @responseField data.city string|null Название города из GeoIP (null если не определено).
     * @responseField data.matched_city_id integer|null ID города в базе зон доставки.
     * @responseField data.matched_city_name string|null Название города в базе зон доставки.
     */
    public function detectCity(): JsonResource
    {
        $ip = request()->ip();
        $cacheKey = 'geoip_city_'.md5($ip);

        $cityName = Cache::remember($cacheKey, 3600, function () use ($ip) {
            try {
                return geoip()->getLocation($ip)->city ?: null;
            } catch (\Throwable) {
                return null;
            }
        });

        $matchedCity = null;

        if ($cityName) {
            $channel = core()->getCurrentChannel();
            $sourceIds = $channel->inventory_sources->pluck('id')->all();

            $matchedCity = DeliveryCity::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)])
                ->where('is_active', true)
                ->whereHas('zones', fn ($q) => $q
                    ->where('is_active', true)
                    ->whereHas('inventory_sources', fn ($qq) => $qq->whereIn('inventory_sources.id', $sourceIds))
                )
                ->first();
        }

        return new JsonResource([
            'data' => [
                'city' => $cityName,
                'matched_city_id' => $matchedCity?->id,
                'matched_city_name' => $matchedCity?->name,
            ],
        ]);
    }
}
