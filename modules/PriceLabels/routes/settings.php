<?php

use Illuminate\Support\Facades\Route;
use Modules\PriceLabels\Http\Controllers\Admin\PriceLabelSettingsController;

Route::get('/', [PriceLabelSettingsController::class, 'index'])
    ->middleware('can:pricelabels.settings.view')
    ->name('index');

Route::post('/fonts', [PriceLabelSettingsController::class, 'storeFont'])
    ->middleware('can:pricelabels.settings.update')
    ->name('fonts.store');

Route::delete('/fonts/{font}', [PriceLabelSettingsController::class, 'destroyFont'])
    ->middleware('can:pricelabels.settings.update')
    ->name('fonts.destroy');
