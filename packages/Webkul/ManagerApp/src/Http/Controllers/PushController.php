<?php

namespace Webkul\ManagerApp\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\PushNotification\Repositories\PushSubscriptionRepository;
use Webkul\PushNotification\Repositories\PushVapidSettingRepository;
use Webkul\PushNotification\Services\WebPushService;
use Webkul\User\Models\Admin;

class PushController extends Controller
{
    public function __construct(
        private readonly PushSubscriptionRepository $subscriptionRepo,
        private readonly PushVapidSettingRepository $vapidRepo,
        private readonly WebPushService $webPush,
    ) {}

    /**
     * GET /manager/api/push/vapid-public-key
     *
     * Returns the VAPID public key for the service worker registration.
     */
    public function vapidPublicKey(): JsonResponse
    {
        $vapid = $this->vapidRepo->getCurrent();

        if (! $vapid) {
            return response()->json(['message' => 'VAPID not configured.'], 503);
        }

        return response()->json(['public_key' => $vapid->public_key]);
    }

    /**
     * POST /manager/api/push/subscribe
     *
     * Save/update a WebPush subscription for the authenticated admin.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'   => 'required|string|url',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
        ]);

        /** @var Admin $admin */
        $admin = $request->user('sanctum');

        $this->subscriptionRepo->upsertForSubscribable(
            Admin::class,
            $admin->id,
            $request->string('endpoint'),
            $request->string('public_key'),
            $request->string('auth_token'),
        );

        return response()->json(['message' => 'Subscribed.']);
    }

    /**
     * DELETE /manager/api/push/subscribe
     *
     * Remove a WebPush subscription.
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string|url',
        ]);

        $this->subscriptionRepo->deleteByEndpoint($request->string('endpoint'));

        return response()->json(['message' => 'Unsubscribed.']);
    }
}
