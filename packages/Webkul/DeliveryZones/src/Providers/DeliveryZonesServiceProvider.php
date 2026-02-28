<?php

namespace Webkul\DeliveryZones\Providers;

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
}
