<?php

use Illuminate\Support\Facades\Route;
use Modules\Widget\Http\Controllers\WidgetController;

/*
|--------------------------------------------------------------------------
| Widget Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Widget module.
|
*/

Route::middleware(['web', 'auth'])->group(function () {
    // Widget manager routes (AJAX + index)
    Route::prefix('panel/widgets')->name('widgets.')->group(function () {
        Route::get('/', [WidgetController::class, 'index'])->name('index');
        Route::post('/update', [WidgetController::class, 'update'])->name('update');
        Route::delete('/destroy', [WidgetController::class, 'destroy'])->name('destroy');
        Route::get('/show', [WidgetController::class, 'showWidget'])->name('show');
    });

    // Settings alias — accessible from the admin sidebar
    Route::get('/panel/settings/widgets', [WidgetController::class, 'index'])
        ->name('settings.widgets.index');
});
