<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\ManagerApp\Listeners\CopyInventorySourceToOrder;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        'checkout.order.save.after' => [
            CopyInventorySourceToOrder::class,
        ],
    ];
}
