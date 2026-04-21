<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\ExternalPayments\Http\Controllers\Admin\InventorySourceConfigController;

Route::group(
    [
        'middleware' => ['admin', NoCacheMiddleware::class],
        'prefix' => config('app.admin_url').'/settings/external-payments',
    ],
    function () {
        Route::controller(InventorySourceConfigController::class)
            ->prefix('inventory-source-configs')
            ->group(function () {
                Route::get('', 'index')->name('admin.external-payments.inventory-source-configs.index');

                Route::get('edit/{inventorySourceId}', 'edit')->name('admin.external-payments.inventory-source-configs.edit');

                Route::put('edit/{inventorySourceId}', 'update')->name('admin.external-payments.inventory-source-configs.update');
            });
    }
);
