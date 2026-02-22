<?php

namespace Webkul\Shipping\Carriers;

use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
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

        $rate = $zone->rates()
            ->where('min_order_total', '<=', $cart->sub_total)
            ->orderByDesc('min_order_total')
            ->orderByDesc('sort_order')
            ->first();

        if (! $rate) {
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
