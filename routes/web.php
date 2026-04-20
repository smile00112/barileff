<?php

use App\Http\Controllers\Dev\CategoryImageCsvImportController;
use Illuminate\Support\Facades\Route;

Route::post('_dev/category-images-from-csv', CategoryImageCsvImportController::class)
    ->name('dev.category-images-from-csv');
