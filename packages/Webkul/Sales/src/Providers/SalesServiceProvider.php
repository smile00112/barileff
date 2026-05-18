<?php

namespace Webkul\Sales\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderStatus;
use Webkul\Sales\Models\OrderStatusTransition;
use Webkul\Sales\Observers\OrderObserver;
use Webkul\Sales\Services\OrderStatusTransitionService;

class SalesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Order::observe(OrderObserver::class);

        // Flush the status/transition cache whenever the tables are mutated.
        $flushCache = fn () => app(OrderStatusTransitionService::class)->flushCache();

        OrderStatus::saved($flushCache);
        OrderStatus::deleted($flushCache);
        OrderStatusTransition::saved($flushCache);
        OrderStatusTransition::deleted($flushCache);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OrderStatusTransitionService::class);
    }
}
