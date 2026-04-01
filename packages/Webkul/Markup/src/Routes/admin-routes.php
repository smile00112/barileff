<?php

use Illuminate\Support\Facades\Route;
use Webkul\Markup\Http\Controllers\Admin\MarkupGroupController;

Route::group([
    'prefix'     => config('app.admin_url', 'admin'),
    'middleware' => ['web', 'admin'],
], function () {
    Route::prefix('markup/groups')->name('admin.markup.groups.')->controller(MarkupGroupController::class)->group(function () {
        Route::get('', 'index')->name('index');
        Route::get('inventory-sources', 'inventorySourcesJson')->name('inventory-sources');
        Route::get('create', 'create')->name('create');
        Route::post('create', 'store')->name('store');
        Route::get('edit/{id}', 'edit')->name('edit');
        Route::put('edit/{id}', 'update')->name('update');
        Route::delete('edit/{id}', 'destroy')->name('destroy');
    });
});
