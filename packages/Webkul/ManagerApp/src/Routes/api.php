<?php

use Illuminate\Support\Facades\Route;
use Webkul\ManagerApp\Http\Controllers\AuthController;
use Webkul\ManagerApp\Http\Controllers\OrderController;
use Webkul\ManagerApp\Http\Controllers\PushController;

Route::prefix('manager/api')->name('manager.api.')->group(function () {
    // Public auth routes
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // VAPID public key is public (needed before auth for SW registration)
    Route::get('push/vapid-public-key', [PushController::class, 'vapidPublicKey'])->name('push.vapid');

    // Authenticated routes
    Route::middleware('manager.authenticate')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Orders
        Route::get('orders/statuses', [OrderController::class, 'statuses'])->name('orders.statuses');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{id}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');

        // Push subscriptions
        Route::post('push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
        Route::delete('push/subscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
    });
});
