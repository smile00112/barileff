<?php

namespace Webkul\DeliveryZones\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DeliveryZonesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'delivery-zones');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen(
            'checkout.cart.collect.totals.before.shipping',
            \Webkul\DeliveryZones\Listeners\RefreshDeliveryZoneRatesOnCartChange::class
        );
    }
}
