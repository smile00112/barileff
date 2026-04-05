<?php

namespace Webkul\DeliveryZones\Http\Controllers\Shop;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\DeliveryZones\Models\DeliveryCity;
use Webkul\DeliveryZones\Services\CartDeliveryZoneManager;
use Webkul\DeliveryZones\Services\ZoneSelector;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Resources\CartResource;

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
                'center_lat' => (float) ($city->center_lat ?? 0),
                'center_lng' => (float) ($city->center_lng ?? 0),
                'polygon_json' => $city->polygon_json ?? [],
                'zones' => $city->zones->map(function ($zone) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'polygon_json' => $zone->polygon_json ?? [],
                        'polygon_color' => (string) ($zone->polygon_color ?? '#0077cc'),
                        'inventory_source_id' => (int) $zone->inventory_sources->first()?->id,
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

        $cart = Cart::getCart();
        if ($cart) {
            if ($city) {
                $address = (new CartAddress)->fill([
                    'city' => $city,
                    'cart_id' => $cart->id,
                ]);
                $address->address_type = CartAddress::ADDRESS_TYPE_SHIPPING;
                $cart->setRelation('shipping_address', $address);
                Cart::setCart($cart);
            }
            app(CartDeliveryZoneManager::class)->applySelection(
                $cart,
                $lat,
                $lng,
                $zoneId ?? $zone->id
            );

            Cart::collectTotals();

            $shippingMethod = $validated['shipping_method'] ?? null;
            if ($shippingMethod === 'delivery_zones_delivery_zones') {
                Cart::saveShippingMethod($shippingMethod);
            }

            $shippingMethods = [];
            if ($cart->haveStockableItems() && $cart->shipping_address) {
                $rates = Shipping::collectRates();
                $shippingMethods = array_values($rates['shippingMethods'] ?? []);
            }
            Cart::collectTotals();

            $cart->refresh();

            return new JsonResource([
                'data' => [
                    'inventory_source_id' => $inventorySourceId,
                    'zone' => [
                        'id' => $zone->id,
                        'name' => $zone->name,
                    ],
                    'cart' => new CartResource($cart),
                    'shipping_methods' => $shippingMethods,
                ],
            ]);
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
}
