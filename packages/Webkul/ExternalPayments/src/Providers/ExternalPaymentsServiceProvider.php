<?php

namespace Webkul\ExternalPayments\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\ExternalPayments\Repositories\InventorySourceConfigRepository;

class ExternalPaymentsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(InventorySourceConfigRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'external-payments');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'external-payments');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->registerRoutes();
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../Config/paymentmethods.php',
            'payment_methods'
        );

        $this->mergeConfigFrom(
            __DIR__.'/../Config/system.php',
            'core'
        );
    }

    /**
     * Register routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware(['web'])
            ->group(__DIR__.'/../Http/routes.php');

        Route::middleware(['web'])
            ->group(__DIR__.'/../Http/admin-routes.php');
    }
}
