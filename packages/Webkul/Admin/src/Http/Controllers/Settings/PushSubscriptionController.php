<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\PushNotification\Repositories\PushSubscriptionRepository;
use Webkul\PushNotification\Repositories\PushVapidSettingRepository;

class PushSubscriptionController extends Controller
{
    public function __construct(
        protected PushSubscriptionRepository $pushSubscriptionRepository,
        protected PushVapidSettingRepository $vapidSettingRepository
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
     * Register a new push subscription for the authenticated admin.
     */
    public function subscribe(): JsonResponse
    {
        $data = $this->validate(request(), [
            'endpoint' => 'required|string',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
        ]);

        $admin = auth()->guard('admin')->user();

        $this->pushSubscriptionRepository->upsertForSubscribable(
            'admin',
            $admin->id,
            $data['endpoint'],
            $data['public_key'],
            $data['auth_token']
        );

        return new JsonResponse(['message' => 'Subscribed successfully.']);
    }

    /**
     * Remove the push subscription for the given endpoint.
     */
    public function unsubscribe(): JsonResponse
    {
        $this->validate(request(), [
            'endpoint' => 'required|string',
        ]);

        $this->pushSubscriptionRepository->deleteByEndpoint(request('endpoint'));

        return new JsonResponse(['message' => 'Unsubscribed successfully.']);
    }
}
