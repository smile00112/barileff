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
 */
class DeliveryZonesController
{
    /**
     * Get cities and zones for the current channel (for map display).
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
     * Select delivery zone (from map click or address). Updates cart and session.
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
