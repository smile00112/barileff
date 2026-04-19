<?php

namespace Webkul\ImportExport\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\ImportExport\Listeners\ProductsBatchSavedListener;

class ImportExportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'import_export');

        $this->mergeConfigFrom(__DIR__.'/../Config/admin-menu.php', 'menu.admin');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        Event::listen('catalog_import.products_saved', [ProductsBatchSavedListener::class, 'handle']);
    }
}
