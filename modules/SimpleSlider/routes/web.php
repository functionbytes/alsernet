<?php

use Illuminate\Support\Facades\Route;
use Modules\SimpleSlider\Http\Controllers\SimpleSliderSettingsController;

Route::middleware(['web', 'auth'])
    ->prefix('panel/settings/simple-sliders')
    ->name('settings.simple-sliders.')
    ->group(function () {
        Route::get('/', [SimpleSliderSettingsController::class, 'index'])->name('index');
        Route::patch('/', [SimpleSliderSettingsController::class, 'update'])->name('update');
    });
