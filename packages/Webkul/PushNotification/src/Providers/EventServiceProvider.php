<?php

namespace Webkul\PushNotification\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\PushNotification\Listeners\PushNotificationDispatcher;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $events = [
            'checkout.order.save.after',
            'sales.order.cancel.after',
            'sales.order.update-status.after',
            'sales.invoice.save.after',
            'sales.shipment.save.after',
            'sales.refund.save.after',
            'customer.create.after',
        ];

        foreach ($events as $eventName) {
            Event::listen($eventName, function ($payload) use ($eventName) {
                app(PushNotificationDispatcher::class)->handle($eventName, $payload);
            });
        }
    }
}
