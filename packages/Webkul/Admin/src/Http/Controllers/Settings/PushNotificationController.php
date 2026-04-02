<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\PushNotification\Listeners\PushNotificationDispatcher;
use Webkul\PushNotification\Repositories\PushNotificationSettingRepository;
use Webkul\PushNotification\Repositories\PushVapidSettingRepository;
use Webkul\PushNotification\Services\WebPushService;

class PushNotificationController extends Controller
{
    public function __construct(
        protected PushNotificationSettingRepository $settingRepository,
        protected PushVapidSettingRepository $vapidSettingRepository,
        protected WebPushService $webPushService
    ) {}

    /**
     * Display the push notification settings page.
     */
    public function index(): \Illuminate\View\View
    {
        $eventMap = PushNotificationDispatcher::getEventMap();
        $vapid = $this->vapidSettingRepository->getCurrent();
        $settings = $this->settingRepository->getAllKeyed();

        return view('admin::settings.push-notifications.index', compact('eventMap', 'vapid', 'settings'));
    }

    /**
     * Update (upsert) a single event+target push notification setting.
     */
    public function update(): JsonResponse
    {
        $data = $this->validate(request(), [
            'event' => 'required|string',
            'target' => 'required|in:admin,customer,both',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $setting = $this->settingRepository->upsertForEvent(
            $data['event'],
            $data['target'],
            [
                'title' => $data['title'],
                'body' => $data['body'],
                'is_active' => $data['is_active'] ?? true,
            ]
        );

        return new JsonResponse([
            'message' => trans('admin::app.settings.push-notifications.update-success'),
            'data' => $setting,
        ]);
    }

    /**
     * Generate new VAPID keys and persist them.
     */
    public function generateVapid(): JsonResponse
    {
        $keys = $this->webPushService->generateVapidKeys();

        $vapid = $this->vapidSettingRepository->updateOrCreateSingleton([
            'public_key' => $keys['publicKey'],
            'private_key' => $keys['privateKey'],
            'subject' => request('subject', 'mailto:admin@'.request()->getHost()),
        ]);

        return new JsonResponse([
            'message' => trans('admin::app.settings.push-notifications.vapid-generated'),
            'public_key' => $vapid->public_key,
        ]);
    }

    /**
     * Update VAPID subject without regenerating keys.
     */
    public function updateVapid(): JsonResponse
    {
        $this->validate(request(), [
            'subject' => 'required|string|max:255',
        ]);

        $vapid = $this->vapidSettingRepository->getCurrent();

        if ($vapid === null) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.push-notifications.vapid-missing'),
            ], 422);
        }

        $vapid->update(['subject' => request('subject')]);

        return new JsonResponse([
            'message' => trans('admin::app.settings.push-notifications.vapid-updated'),
        ]);
    }
}
