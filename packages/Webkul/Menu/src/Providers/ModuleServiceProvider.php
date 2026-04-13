<?php

namespace Webkul\Menu\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models to register.
     *
     * @var array
     */
    protected $models = [
        \Webkul\Menu\Models\Menu::class,
        \Webkul\Menu\Models\MenuItem::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->register(MenuServiceProvider::class);
    }
}
