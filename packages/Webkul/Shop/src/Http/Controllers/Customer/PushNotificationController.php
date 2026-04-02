<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Illuminate\Http\JsonResponse;
use Webkul\PushNotification\Repositories\InPageNotificationRepository;
use Webkul\PushNotification\Repositories\PushSubscriptionRepository;
use Webkul\PushNotification\Repositories\PushVapidSettingRepository;
use Webkul\Shop\Http\Controllers\Controller;

class PushNotificationController extends Controller
{
    public function __construct(
        protected PushSubscriptionRepository $pushSubscriptionRepository,
        protected PushVapidSettingRepository $vapidSettingRepository,
        protected InPageNotificationRepository $inPageNotificationRepository
    ) {}

    /**
     * Return the VAPID public key for the JS client.
     */
    public function vapidPublicKey(): JsonResponse
    {
        $vapid = $this->vapidSettingRepository->getCurrent();

        return new JsonResponse([
            'public_key' => $vapid?->public_key,
        ]);
    }

    /**
     * Register a push subscription for the authenticated customer.
     */
    public function subscribe(): JsonResponse
    {
        $data = $this->validate(request(), [
            'endpoint' => 'required|string',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
        ]);

        $customer = auth()->guard('customer')->user();

        $this->pushSubscriptionRepository->upsertForSubscribable(
            'customer',
            $customer->id,
            $data['endpoint'],
            $data['public_key'],
            $data['auth_token']
        );

        return new JsonResponse(['message' => 'Subscribed successfully.']);
    }

    /**
     * Remove a push subscription by endpoint.
     */
    public function unsubscribe(): JsonResponse
    {
        $this->validate(request(), [
            'endpoint' => 'required|string',
        ]);

        $this->pushSubscriptionRepository->deleteByEndpoint(request('endpoint'));

        return new JsonResponse(['message' => 'Unsubscribed successfully.']);
    }

    /**
     * Return unread in-page notifications for the authenticated customer.
     */
    public function notifications(): JsonResponse
    {
        $customer = auth()->guard('customer')->user();
        $notifications = $this->inPageNotificationRepository->getUnreadForCustomer($customer->id);

        return new JsonResponse([
            'data' => $notifications,
        ]);
    }

    /**
     * Mark all in-page notifications as read.
     */
    public function markAllRead(): JsonResponse
    {
        $customer = auth()->guard('customer')->user();

        $this->inPageNotificationRepository->markAllReadForCustomer($customer->id);

        return new JsonResponse(['message' => 'All notifications marked as read.']);
    }

    /**
     * Mark a single in-page notification as read.
     */
    public function markRead(int $id): JsonResponse
    {
        $this->inPageNotificationRepository->markRead($id);

        return new JsonResponse(['message' => 'Notification marked as read.']);
    }
}
