<?php

namespace Webkul\FPC\Providers;

use Illuminate\Support\ServiceProvider;
use Webkul\FPC\Console\Commands\WarmCategoryMenuCacheCommand;

class FPCServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->register(EventServiceProvider::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                WarmCategoryMenuCacheCommand::class,
            ]);
        }
    }
}
