<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ManagerAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);

        $this->app['router']->aliasMiddleware(
            'manager.authenticate',
            \Webkul\ManagerApp\Http\Middleware\ManagerAuthenticate::class
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'manager-app');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        Route::middleware(['api'])->group(__DIR__.'/../Routes/api.php');

        Route::middleware(['web'])->group(__DIR__.'/../Routes/web.php');
    }
}
