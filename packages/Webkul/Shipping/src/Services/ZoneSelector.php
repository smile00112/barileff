<?php

namespace Webkul\Shipping\Services;

use Webkul\Checkout\Contracts\Cart as CartContract;
use Webkul\Shipping\Models\DeliveryZone;

class ZoneSelector
{
    public function resolveZone(CartContract $cart): ?DeliveryZone
    {
        $cart->loadMissing(['channel.inventory_sources', 'shipping_address', 'delivery_zone']);

        $sourceIds = $cart->channel?->inventory_sources?->pluck('id')->all() ?? [];

        if (empty($sourceIds)) {
            return null;
        }

        if (
            $cart->delivery_zone_mode === 'manual'
            && $cart->delivery_zone_id
        ) {
            $manualZone = DeliveryZone::query()
                ->where('id', $cart->delivery_zone_id)
                ->where('is_active', true)
                ->whereHas('inventory_sources', function ($query) use ($sourceIds) {
                    $query->whereIn('inventory_sources.id', $sourceIds);
                })
                ->first();

            if ($manualZone) {
                return $manualZone;
            }
        }

        if (
            $cart->delivery_point_lat === null
            || $cart->delivery_point_lng === null
        ) {
            return null;
        }

        $cityName = trim((string) $cart->shipping_address?->city);

        if ($cityName === '') {
            return null;
        }

        $zones = DeliveryZone::query()
            ->with('city')
            ->where('is_active', true)
            ->whereHas('city', function ($query) use ($cityName) {
                $query->whereRaw('LOWER(name) = ?', [mb_strtolower($cityName)]);
            })
            ->whereHas('inventory_sources', function ($query) use ($sourceIds) {
                $query->whereIn('inventory_sources.id', $sourceIds);
            })
            ->get();

        foreach ($zones as $zone) {
            if ($this->pointInPolygon((float) $cart->delivery_point_lat, (float) $cart->delivery_point_lng, $zone->polygon_json ?? [])) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, float|string>>  $polygon
     */
    protected function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        if (count($polygon) < 3) {
            return false;
        }

        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            $latI = (float) ($polygon[$i][0] ?? 0);
            $lngI = (float) ($polygon[$i][1] ?? 0);
            $latJ = (float) ($polygon[$j][0] ?? 0);
            $lngJ = (float) ($polygon[$j][1] ?? 0);

            $intersects = (($lngI > $lng) !== ($lngJ > $lng))
                && ($lat < (($latJ - $latI) * ($lng - $lngI) / (($lngJ - $lngI) ?: 0.0000001) + $latI));

            if ($intersects) {
                $inside = ! $inside;
            }

            $j = $i;
        }

        return $inside;
    }
}
