<?php

namespace Webkul\DeliveryZones\Services;

use Webkul\Checkout\Contracts\Cart as CartContract;
use Webkul\DeliveryZones\Models\DeliveryZone;
use Webkul\DeliveryZones\Models\DeliveryZoneRate;

class DeliveryZoneRateResolver
{
    public function resolveRate(CartContract $cart): ?DeliveryZoneRate
    {
        $zone = app(ZoneSelector::class)->resolveZone($cart);

        if (! $zone) {
            return null;
        }

        return $this->resolveRateForZone($cart, $zone);
    }

    public function resolveRateForZone(CartContract $cart, DeliveryZone $zone): ?DeliveryZoneRate
    {
        $subTotal = $cart->sub_total ?? 0;

        return $zone->rates()
            ->where('min_order_total', '<=', $subTotal)
            ->orderByDesc('min_order_total')
            ->orderByDesc('sort_order')
            ->first();
    }
}
