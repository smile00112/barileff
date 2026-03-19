<?php

namespace Webkul\Markup\Providers;

use Illuminate\Support\ServiceProvider;

class MarkupServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'markup');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'markup');

        $this->mergeConfigFrom(__DIR__.'/../Config/admin-menu.php', 'menu.admin');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }
}
