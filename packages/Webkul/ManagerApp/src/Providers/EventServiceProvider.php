<?php

namespace Webkul\ManagerApp\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Webkul\ManagerApp\Listeners\BroadcastOrderEvents;
use Webkul\ManagerApp\Listeners\CopyInventorySourceToOrder;
use Webkul\ManagerApp\Listeners\SendOrderPushNotification;

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
            BroadcastOrderEvents::class,
            SendOrderPushNotification::class,
        ],
    ];
}
