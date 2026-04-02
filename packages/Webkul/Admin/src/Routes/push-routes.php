<?php

use Illuminate\Support\Facades\Route;
use Webkul\Admin\Http\Controllers\Settings\PushSubscriptionController;

/**
 * Admin push subscription routes.
 */
Route::controller(PushSubscriptionController::class)->prefix('push')->group(function () {
    Route::get('vapid-public-key', 'vapidPublicKey')->name('admin.push.vapid_public_key');

    Route::post('subscribe', 'subscribe')->name('admin.push.subscribe');

    Route::delete('unsubscribe', 'unsubscribe')->name('admin.push.unsubscribe');
});
