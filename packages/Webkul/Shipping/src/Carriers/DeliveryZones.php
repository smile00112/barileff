<?php

namespace Webkul\Shipping\Carriers;

use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\DeliveryZones\Services\DeliveryZoneRateResolver;
use Webkul\Shipping\Services\ZoneSelector;

class DeliveryZones extends AbstractShipping
{
    /**
     * @var string
     */
    protected $code = 'delivery_zones';

    /**
     * @var string
     */
    protected $method = 'delivery_zones_delivery_zones';

    /**
     * @return CartShippingRate|false
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $cart = Cart::getCart();

        if (! $cart) {
            return false;
        }

        $zone = app(ZoneSelector::class)->resolveZone($cart);

        if (! $zone) {
            Log::warning('Delivery zone is not resolved', [
                'cart_id' => $cart->id,
                'delivery_zone_id' => $cart->delivery_zone_id,
            ]);

            return false;
        }

        $resolver = app(DeliveryZoneRateResolver::class);

        $rate = $resolver->resolveRateForZone($cart, $zone);

        if (! $rate) {
            $minOrderTotal = $resolver->getZoneMinimumOrderTotal($zone);

            if ($minOrderTotal !== null && (float) ($cart->sub_total ?? 0) < $minOrderTotal) {
                Log::info('Delivery zone rate skipped: cart sub_total below zone minimum', [
                    'cart_id' => $cart->id,
                    'zone_id' => $zone->id,
                    'sub_total' => (float) ($cart->sub_total ?? 0),
                    'zone_min_order_total' => $minOrderTotal,
                ]);
            }

            return false;
        }

        $cartShippingRate = new CartShippingRate;
        $cartShippingRate->carrier = $this->getCode();
        $cartShippingRate->carrier_title = $this->getConfigData('title');
        $cartShippingRate->method = $this->getMethod();
        $cartShippingRate->method_title = $zone->name;
        $cartShippingRate->method_description = $this->getConfigData('description');
        $cartShippingRate->price = core()->convertPrice($rate->price);
        $cartShippingRate->base_price = (float) $rate->price;

        return $cartShippingRate;
    }
}
