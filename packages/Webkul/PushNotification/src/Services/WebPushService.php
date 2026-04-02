<?php

namespace Webkul\PushNotification\Services;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;
use Webkul\PushNotification\Models\PushSubscription;
use Webkul\PushNotification\Repositories\PushSubscriptionRepository;
use Webkul\PushNotification\Repositories\PushVapidSettingRepository;

class WebPushService
{
    public function __construct(
        protected PushVapidSettingRepository $vapidSettingRepository,
        protected PushSubscriptionRepository $pushSubscriptionRepository
    ) {}

    /**
     * Generate a new VAPID key pair.
     *
     * @return array{publicKey: string, privateKey: string}
     */
    public function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }

    /**
     * Send a push notification to all admin subscriptions.
     */
    public function sendToAdmins(string $title, string $body, ?string $url = null): void
    {
        $subscriptions = $this->pushSubscriptionRepository->getAdminSubscriptions();

        $this->sendToSubscriptions($subscriptions, $title, $body, $url);
    }

    /**
     * Send a push notification to a specific customer's subscriptions.
     */
    public function sendToCustomer(int $customerId, string $title, string $body, ?string $url = null): void
    {
        $subscriptions = $this->pushSubscriptionRepository->getCustomerSubscriptions($customerId);

        $this->sendToSubscriptions($subscriptions, $title, $body, $url);
    }

    /**
     * Send to a collection of subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, PushSubscription>  $subscriptions
     */
    public function sendToSubscriptions(\Illuminate\Database\Eloquent\Collection $subscriptions, string $title, string $body, ?string $url = null): void
    {
        if ($subscriptions->isEmpty()) {
            return;
        }

        $webPush = $this->buildWebPush();

        if ($webPush === null) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);

        $staleEndpoints = [];

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => 'aesgcm',
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                $staleEndpoints[] = $report->getEndpoint();
            }
        }

        if (! empty($staleEndpoints)) {
            $this->pushSubscriptionRepository->model
                ->whereIn('endpoint', $staleEndpoints)
                ->delete();
        }
    }

    /**
     * Build the WebPush instance with VAPID credentials from the database.
     */
    protected function buildWebPush(): ?WebPush
    {
        $vapid = $this->vapidSettingRepository->getCurrent();

        if ($vapid === null) {
            return null;
        }

        return new WebPush([
            'VAPID' => [
                'subject' => $vapid->subject,
                'publicKey' => $vapid->public_key,
                'privateKey' => $vapid->private_key,
            ],
        ]);
    }
}
