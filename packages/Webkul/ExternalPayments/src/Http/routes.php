<?php

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Webkul\ExternalPayments\Http\Controllers\PaymentController;
use Webkul\ExternalPayments\Http\Controllers\WebhookController;

Route::group(['middleware' => ['web']], function () {
    Route::prefix('external-payments')->group(function () {
        Route::get('/redirect', [PaymentController::class, 'redirect'])->name('external-payments.redirect');

        Route::get('/success', [PaymentController::class, 'success'])->name('external-payments.success');

        Route::get('/cancel', [PaymentController::class, 'cancel'])->name('external-payments.cancel');
    });
});

Route::post('external-payments/webhook', [WebhookController::class, 'handle'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->name('external-payments.webhook');
