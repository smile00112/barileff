<?php

use Illuminate\Support\Facades\Route;
use Webkul\PaymentConfirmation\Http\Controllers\Shop\ReceiptController;

Route::middleware(['customer'])
    ->prefix('payment-confirmation')
    ->name('shop.payment-confirmation.')
    ->group(function () {
        Route::post('upload/{orderId}', [ReceiptController::class, 'upload'])
            ->name('upload');
    });
