<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Support\Facades\Broadcast;
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

        $this->registerBroadcastChannels();
    }

    /**
     * Register private channel authorization for warehouse channels.
     *
     * Channel: manager.warehouse.{sourceId}
     * Only admins with that inventory source assigned can subscribe.
     */
    protected function registerBroadcastChannels(): void
    {
        Broadcast::channel('manager.warehouse.{sourceId}', function ($user, int $sourceId) {
            if (! $user instanceof \Webkul\User\Models\Admin) {
                return false;
            }

            return in_array($sourceId, $user->getRestrictedInventorySourceIds(), true);
        });
    }
}
