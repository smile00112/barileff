<?php

use Webkul\PushNotification\Models\InPageNotification;
use Webkul\PushNotification\Models\PushNotificationSetting;
use Webkul\PushNotification\Models\PushSubscription;
use Webkul\PushNotification\Models\PushVapidSetting;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------

it('returns the push notification settings page for admin', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.push_notifications.index'))
        ->assertOk()
        ->assertSeeText('Push Notifications');
});

// ---------------------------------------------------------------------------
// Update (upsert) setting
// ---------------------------------------------------------------------------

it('saves a push notification setting', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.push_notifications.update'), [
        'event' => 'checkout.order.save.after',
        'target' => 'admin',
        'title' => 'New Order #{order_id}',
        'body' => 'A new order has been placed.',
        'is_active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.push-notifications.update-success'));

    $this->assertDatabaseHas('push_notification_settings', [
        'event' => 'checkout.order.save.after',
        'target' => 'admin',
        'title' => 'New Order #{order_id}',
    ]);
});

it('updates an existing push notification setting', function () {
    $this->loginAsAdmin();

    PushNotificationSetting::create([
        'event' => 'sales.order.cancel.after',
        'target' => 'both',
        'title' => 'Old Title',
        'body' => 'Old body.',
        'is_active' => true,
    ]);

    postJson(route('admin.settings.push_notifications.update'), [
        'event' => 'sales.order.cancel.after',
        'target' => 'both',
        'title' => 'Order Cancelled #{order_id}',
        'body' => 'Your order was cancelled.',
        'is_active' => false,
    ])
        ->assertOk();

    $this->assertDatabaseHas('push_notification_settings', [
        'event' => 'sales.order.cancel.after',
        'title' => 'Order Cancelled #{order_id}',
        'is_active' => false,
    ]);
});

it('fails validation when required fields are missing for push notification update', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.push_notifications.update'), [])
        ->assertJsonValidationErrorFor('event')
        ->assertJsonValidationErrorFor('target')
        ->assertJsonValidationErrorFor('title')
        ->assertJsonValidationErrorFor('body')
        ->assertUnprocessable();
});

it('rejects invalid target value for push notification setting', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.push_notifications.update'), [
        'event' => 'checkout.order.save.after',
        'target' => 'invalid_target',
        'title' => 'Test',
        'body' => 'Test body',
    ])
        ->assertJsonValidationErrorFor('target')
        ->assertUnprocessable();
});

// ---------------------------------------------------------------------------
// VAPID key generation
// ---------------------------------------------------------------------------

it('generates VAPID keys and persists them', function () {
    $this->loginAsAdmin();

    $fakeKeys = [
        'publicKey' => 'BFakePublicKeyBase64UrlEncoded',
        'privateKey' => 'FakePrivateKeyBase64UrlEncoded',
    ];

    $this->mock(\Webkul\PushNotification\Services\WebPushService::class, function ($mock) use ($fakeKeys) {
        $mock->shouldReceive('generateVapidKeys')->once()->andReturn($fakeKeys);
    });

    postJson(route('admin.settings.push_notifications.vapid.generate'), [
        'subject' => 'mailto:test@example.com',
    ])
        ->assertOk()
        ->assertJsonStructure(['message', 'public_key'])
        ->assertJsonPath('message', trans('admin::app.settings.push-notifications.vapid-generated'));

    expect(PushVapidSetting::count())->toBe(1);
    expect(PushVapidSetting::first()->public_key)->toBe($fakeKeys['publicKey']);
});

it('updates VAPID subject without regenerating keys', function () {
    $this->loginAsAdmin();

    PushVapidSetting::create([
        'public_key' => 'fake-public-key',
        'private_key' => 'fake-private-key',
        'subject' => 'mailto:old@example.com',
    ]);

    putJson(route('admin.settings.push_notifications.vapid.update'), [
        'subject' => 'mailto:new@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.push-notifications.vapid-updated'));

    expect(PushVapidSetting::first()->subject)->toBe('mailto:new@example.com');
});

it('returns 422 when updating VAPID subject with no keys configured', function () {
    $this->loginAsAdmin();

    putJson(route('admin.settings.push_notifications.vapid.update'), [
        'subject' => 'mailto:test@example.com',
    ])
        ->assertUnprocessable();
});

// ---------------------------------------------------------------------------
// Admin push subscription
// ---------------------------------------------------------------------------

it('admin can subscribe to push notifications', function () {
    $admin = $this->loginAsAdmin();

    postJson(route('admin.push.subscribe'), [
        'endpoint' => 'https://fcm.example.com/push/endpoint123',
        'public_key' => 'some-p256dh-key',
        'auth_token' => 'some-auth-token',
    ])
        ->assertOk();

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_type' => 'admin',
        'subscribable_id' => $admin->id,
        'endpoint' => 'https://fcm.example.com/push/endpoint123',
    ]);
});

it('admin can unsubscribe from push notifications', function () {
    $admin = $this->loginAsAdmin();

    PushSubscription::create([
        'subscribable_type' => 'admin',
        'subscribable_id' => $admin->id,
        'endpoint' => 'https://fcm.example.com/push/old-endpoint',
        'public_key' => 'some-key',
        'auth_token' => 'some-auth',
    ]);

    deleteJson(route('admin.push.unsubscribe'), [
        'endpoint' => 'https://fcm.example.com/push/old-endpoint',
    ])
        ->assertOk();

    $this->assertDatabaseMissing('push_subscriptions', [
        'endpoint' => 'https://fcm.example.com/push/old-endpoint',
    ]);
});

// ---------------------------------------------------------------------------
// In-page notification polling (shop)
// ---------------------------------------------------------------------------

it('customer can fetch unread in-page notifications', function () {
    $customer = \Webkul\Customer\Models\Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    InPageNotification::create([
        'customer_id' => $customer->id,
        'title' => 'Your order was shipped',
        'body' => 'Order #123 is on its way.',
        'url' => '/orders/123',
        'read_at' => null,
    ]);

    \Pest\Laravel\getJson(route('shop.customers.push.notifications'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Your order was shipped');
});

it('customer can mark all in-page notifications as read', function () {
    $customer = \Webkul\Customer\Models\Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    InPageNotification::create([
        'customer_id' => $customer->id,
        'title' => 'Test',
        'body' => 'Body',
        'read_at' => null,
    ]);

    \Pest\Laravel\postJson(route('shop.customers.push.mark_all_read'))
        ->assertOk();

    expect(
        InPageNotification::where('customer_id', $customer->id)->whereNull('read_at')->count()
    )->toBe(0);
});

// ---------------------------------------------------------------------------
// PushNotificationDispatcher — placeholder resolution
// ---------------------------------------------------------------------------

it('dispatcher replaces placeholders in notification text', function () {
    $dispatcher = new \Webkul\PushNotification\Listeners\PushNotificationDispatcher(
        app(\Webkul\PushNotification\Repositories\PushNotificationSettingRepository::class),
        app(\Webkul\PushNotification\Repositories\InPageNotificationRepository::class),
        app(\Webkul\PushNotification\Services\WebPushService::class)
    );

    $reflection = new ReflectionClass($dispatcher);
    $method = $reflection->getMethod('replacePlaceholders');
    $method->setAccessible(true);

    $result = $method->invoke($dispatcher, 'Order #{order_id} for {customer_name}', [
        'order_id' => '42',
        'customer_name' => 'John Doe',
    ]);

    expect($result)->toBe('Order #42 for John Doe');
});
