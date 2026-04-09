<?php

namespace Webkul\ManagerApp\Listeners;

use Illuminate\Support\Facades\Log;
use Webkul\ManagerApp\Services\ManagerPushService;
use Webkul\Sales\Contracts\Order;

class SendOrderPushNotification
{
    public function __construct(private readonly ManagerPushService $pushService) {}

    /**
     * Send a push notification to warehouse managers when a new order arrives.
     *
     * Listens to: checkout.order.save.after
     */
    public function handle(Order $order): void
    {
        if ($order->inventory_source_id === null) {
            return;
        }

        try {
            $this->pushService->sendToManagersForInventorySource(
                $order->inventory_source_id,
                'New Order #'.$order->increment_id,
                'Grand total: '.$order->grand_total.' '.$order->base_currency_code,
                url('/manager#/orders/'.$order->id),
            );
        } catch (\Throwable $e) {
            Log::warning('ManagerApp: push notification failed for order '.$order->id.': '.$e->getMessage());
        }
    }
}
