<?php

namespace Webkul\PaymentConfirmation\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PaymentConfirmationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'paymentconfirmation');
        $this->registerRoutes();
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/paymentmethods.php', 'payment_methods');
        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'admin'])
            ->prefix(config('app.admin_url'))
            ->group(__DIR__.'/../Routes/admin-web.php');

        Route::middleware(['web'])
            ->group(__DIR__.'/../Routes/shop-web.php');
    }
}
