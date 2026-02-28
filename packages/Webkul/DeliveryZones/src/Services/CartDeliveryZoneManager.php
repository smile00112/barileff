<?php

namespace Webkul\DeliveryZones\Services;

use Webkul\Checkout\Contracts\Cart as CartContract;

class CartDeliveryZoneManager
{
    public function applySelection(CartContract $cart, ?float $deliveryPointLat, ?float $deliveryPointLng, ?int $deliveryZoneId): void
    {
        $cart->delivery_point_lat = $deliveryPointLat;
        $cart->delivery_point_lng = $deliveryPointLng;
        $cart->delivery_zone_id = $deliveryZoneId;
        $cart->delivery_zone_mode = $this->resolveMode($deliveryPointLat, $deliveryPointLng, $deliveryZoneId);
        $cart->save();
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
