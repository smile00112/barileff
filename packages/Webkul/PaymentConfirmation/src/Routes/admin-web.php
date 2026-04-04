<?php

use Illuminate\Support\Facades\Route;
use Webkul\PaymentConfirmation\Http\Controllers\Admin\OrderReceiptController;
use Webkul\PaymentConfirmation\Http\Controllers\Admin\PaymentDetailController;

Route::prefix('payment-confirmation')->name('admin.payment-confirmation.')->group(function () {
    Route::resource('payment-details', PaymentDetailController::class)
        ->except(['show']);

    Route::post('approve/{orderId}', [OrderReceiptController::class, 'approve'])
        ->name('approve');
});
