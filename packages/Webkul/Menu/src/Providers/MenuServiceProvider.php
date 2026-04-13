<?php

namespace Webkul\Menu\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'menu');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'menu');

        Route::middleware(['web', \Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../Routes/admin.php');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerContracts();
        $this->app->singleton('menuManager', function ($app) {
            return $app->make(\Webkul\Menu\Helpers\MenuManager::class);
        });
    }

    /**
     * Register config files.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');
    }

    /**
     * Register model contracts.
     */
    protected function registerContracts(): void
    {
        $this->app->bind(
            \Webkul\Menu\Contracts\Menu::class,
            \Webkul\Menu\Models\Menu::class
        );

        $this->app->bind(
            \Webkul\Menu\Contracts\MenuItem::class,
            \Webkul\Menu\Models\MenuItem::class
        );
    }
}
