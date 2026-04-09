<?php

use Illuminate\Support\Facades\Route;

// Catch-all SPA shell: all /manager/* requests return the Vue app
Route::get('/manager/{any?}', function () {
    return view('manager-app::app');
})->where('any', '^(?!api).*$')->name('manager.app');
