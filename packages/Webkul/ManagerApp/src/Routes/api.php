<?php

use Illuminate\Support\Facades\Route;
use Webkul\ManagerApp\Http\Controllers\AuthController;

Route::prefix('manager/api')->name('manager.api.')->group(function () {
    // Public auth routes
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Authenticated routes
    Route::middleware('manager.authenticate')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });
});
