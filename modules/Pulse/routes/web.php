<?php

use Illuminate\Support\Facades\Route;
use Modules\Pulse\Http\Controllers\PulseController;

/*
|--------------------------------------------------------------------------
| Pulse Module Routes
|--------------------------------------------------------------------------
|
| Rutas para el módulo de monitoreo Laravel Pulse
| Prefix: /pulse (aplicado por ServiceProvider)
| Name: pulse.* (aplicado por ServiceProvider)
*/

// Dashboard principal de Pulse
Route::get('/', [PulseController::class, 'index'])->name('dashboard');
