<?php

namespace Webkul\DeliveryZones\Services;

use Webkul\Checkout\Contracts\Cart as CartContract;

class CartDeliveryZoneManager
{
    public function __construct(
        protected ZoneSelector $zoneSelector
    ) {}

    public function applySelection(CartContract $cart, ?float $deliveryPointLat, ?float $deliveryPointLng, ?int $deliveryZoneId): void
    {
        $cart->delivery_point_lat = $deliveryPointLat;
        $cart->delivery_point_lng = $deliveryPointLng;
        $cart->delivery_zone_id = $deliveryZoneId;
        $cart->delivery_zone_mode = $this->resolveMode($deliveryPointLat, $deliveryPointLng, $deliveryZoneId);
        $cart->save();

        $zone = $this->zoneSelector->resolveZone($cart);

        if ($zone) {
            $inventorySourceId = (int) $zone->inventory_sources->first()?->id;
            if ($inventorySourceId) {
                $cart->inventory_source_id = $inventorySourceId;
                $cart->save();
                session(['selected_inventory_source_id' => $inventorySourceId]);
            } else {
                $this->clearInventorySource($cart);
            }
        } else {
            $this->clearInventorySource($cart);
        }
    }

    protected function clearInventorySource(CartContract $cart): void
    {
        $cart->inventory_source_id = null;
        $cart->save();
        session()->forget('selected_inventory_source_id');
    }

    public function resolveMode(?float $deliveryPointLat, ?float $deliveryPointLng, ?int $deliveryZoneId): ?string
    {
        if ($deliveryZoneId) {
            return 'manual';
        }

        if ($deliveryPointLat && $deliveryPointLng) {
            return 'auto';
        }

        return null;
    }
}
