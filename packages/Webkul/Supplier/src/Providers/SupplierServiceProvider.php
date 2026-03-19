<?php

namespace Webkul\Supplier\Providers;

use Illuminate\Support\ServiceProvider;

class SupplierServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'supplier');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'supplier');
    }
}
