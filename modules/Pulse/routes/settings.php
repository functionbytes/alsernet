<?php

use Illuminate\Support\Facades\Route;
use Modules\Pulse\Http\Controllers\PulseSettingsController;

/*
|--------------------------------------------------------------------------
| Pulse Settings Routes
|--------------------------------------------------------------------------
|
| Rutas para la configuración del módulo Pulse
| Prefix: /setting/pulse (aplicado por RouteServiceProvider)
| Name: settings.pulse.* (aplicado por RouteServiceProvider)
*/

Route::middleware('can:manage-pulse-backups')->group(function () {
    Route::get('/', [PulseSettingsController::class, 'index'])->name('index');
    Route::post('/update', [PulseSettingsController::class, 'update'])->name('update');
});
