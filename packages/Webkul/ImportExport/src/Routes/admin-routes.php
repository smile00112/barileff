<?php

use Illuminate\Support\Facades\Route;
use Webkul\ImportExport\Http\Controllers\Admin\Catalog\ImportController as CatalogImportController;
use Webkul\ImportExport\Http\Controllers\Admin\DataTransfer\ImportController as DataTransferImportController;

Route::group([
    'prefix' => config('app.admin_url', 'admin'),
    'middleware' => ['web', 'admin'],
], function () {
    /**
     * Catalog import routes.
     */
    Route::controller(CatalogImportController::class)->prefix('catalog/imports')->group(function () {
        Route::get('', 'index')->name('admin.catalog.imports.index');

        Route::get('create', 'create')->name('admin.catalog.imports.create');

        Route::post('create', 'store')->name('admin.catalog.imports.store');

        Route::post('mass-delete', 'massDestroy')->name('admin.catalog.imports.mass_delete');

        Route::delete('{id}', 'destroy')->name('admin.catalog.imports.delete');

        Route::get('{id}', 'show')->name('admin.catalog.imports.show');

        Route::post('{id}/start', 'start')->name('admin.catalog.imports.start');

        Route::get('{id}/status', 'status')->name('admin.catalog.imports.status');
    });

    /**
     * Data Transfer import routes.
     */
    Route::prefix('settings/data-transfer')->group(function () {
        Route::controller(DataTransferImportController::class)->prefix('imports')->group(function () {
            Route::get('', 'index')->name('admin.settings.data_transfer.imports.index');

            Route::get('create', 'create')->name('admin.settings.data_transfer.imports.create');

            Route::post('create', 'store')->name('admin.settings.data_transfer.imports.store');

            Route::get('edit/{id}', 'edit')->name('admin.settings.data_transfer.imports.edit');

            Route::put('update/{id}', 'update')->name('admin.settings.data_transfer.imports.update');

            Route::delete('destroy/{id}', 'destroy')->name('admin.settings.data_transfer.imports.delete');

            Route::get('import/{id}', 'import')->name('admin.settings.data_transfer.imports.import');

            Route::get('validate/{id}', 'validateImport')->name('admin.settings.data_transfer.imports.validate');

            Route::get('start/{id}', 'start')->name('admin.settings.data_transfer.imports.start');

            Route::get('link/{id}', 'link')->name('admin.settings.data_transfer.imports.link');

            Route::get('index/{id}', 'indexData')->name('admin.settings.data_transfer.imports.index_data');

            Route::get('stats/{id}/{state?}', 'stats')->name('admin.settings.data_transfer.imports.stats');

            Route::get('download-sample/{type}/{format}', 'downloadSample')->name('admin.settings.data_transfer.imports.download_sample');

            Route::get('download/{id}', 'download')->name('admin.settings.data_transfer.imports.download');

            Route::get('download-error-report/{id}', 'downloadErrorReport')->name('admin.settings.data_transfer.imports.download_error_report');
        });
    });
});
