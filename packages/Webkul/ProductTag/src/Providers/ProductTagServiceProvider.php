<?php

namespace Webkul\ProductTag\Providers;

use Illuminate\Support\ServiceProvider;

class ProductTagServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'product_tag');
    }
}
