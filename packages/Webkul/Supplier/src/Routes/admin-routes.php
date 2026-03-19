<?php

use Illuminate\Support\Facades\Route;
use Webkul\Supplier\Http\Controllers\Admin\SupplierController;

Route::group([
    'prefix' => config('app.admin_url', 'admin'),
    'middleware' => ['web', 'admin'],
], function () {
    Route::prefix('suppliers')->name('admin.suppliers.')->controller(SupplierController::class)->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('create', 'create')->name('create');
        Route::post('create', 'store')->name('store');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::put('edit/{id}', 'update')->name('update');
        Route::delete('edit/{id}', 'destroy')->name('destroy');
    });
});
