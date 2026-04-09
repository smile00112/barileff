<?php

namespace Webkul\ManagerApp\Services;

use Webkul\PushNotification\Repositories\PushSubscriptionRepository;
use Webkul\PushNotification\Services\WebPushService;
use Webkul\User\Models\Admin;

class ManagerPushService
{
    public function __construct(
        private readonly WebPushService $webPush,
        private readonly PushSubscriptionRepository $subscriptionRepo,
    ) {}

    /**
     * Send a push notification to all managers assigned to a given inventory source.
     *
     * Finds all Admin users who have the specified inventory source and sends
     * a WebPush notification to each of their subscriptions.
     */
    public function sendToManagersForInventorySource(
        int $inventorySourceId,
        string $title,
        string $body,
        ?string $url = null,
    ): void {
        $adminIds = Admin::whereHas('inventorySources', function ($q) use ($inventorySourceId) {
            $q->where('inventory_sources.id', $inventorySourceId);
        })->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $subscriptions = $this->subscriptionRepo->model
            ->where('subscribable_type', Admin::class)
            ->whereIn('subscribable_id', $adminIds)
            ->get();

        $this->webPush->sendToSubscriptions($subscriptions, $title, $body, $url);
    }
}
