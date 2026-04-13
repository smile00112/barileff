<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Menu\Http\Controllers\Admin\MenuController;
use Webkul\Menu\Http\Controllers\Admin\MenuItemController;

Route::group([
    'middleware' => ['admin', NoCacheMiddleware::class],
    'prefix' => config('app.admin_url'),
], function () {
    Route::controller(MenuController::class)->prefix('menu/menus')->group(function () {
        Route::get('/', 'index')->name('admin.menu.menus.index');
        Route::get('/create', 'create')->name('admin.menu.menus.create');
        Route::post('/create', 'store')->name('admin.menu.menus.store');
        Route::get('/{id}/edit', 'edit')->name('admin.menu.menus.edit');
        Route::put('/{id}', 'update')->name('admin.menu.menus.update');
        Route::delete('/{id}', 'delete')->name('admin.menu.menus.delete');
    });

    Route::controller(MenuItemController::class)->prefix('menu/menus/{menuId}/items')->group(function () {
        Route::get('/', 'index')->name('admin.menu.items.index');
        Route::post('/', 'store')->name('admin.menu.items.store');
        Route::put('/{id}', 'update')->name('admin.menu.items.update');
        Route::delete('/{id}', 'delete')->name('admin.menu.items.delete');
        Route::post('/sort', 'sort')->name('admin.menu.items.sort');
    });
});
