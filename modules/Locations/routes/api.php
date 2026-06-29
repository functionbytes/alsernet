<?php

use Illuminate\Support\Facades\Route;
use Modules\Locations\Http\Controllers\LocationApiController;

Route::middleware(['api'])
    ->prefix('api/locations')
    ->name('api.locations.')
    ->group(function () {
        Route::get('/countries', [LocationApiController::class, 'countries'])->name('countries');
        Route::get('/states', [LocationApiController::class, 'states'])->name('states');
        Route::get('/cities', [LocationApiController::class, 'cities'])->name('cities');
    });
