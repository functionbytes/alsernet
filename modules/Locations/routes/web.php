<?php

use Illuminate\Support\Facades\Route;
use Modules\Locations\Http\Controllers\CityController;
use Modules\Locations\Http\Controllers\CountryController;
use Modules\Locations\Http\Controllers\LocationImportController;
use Modules\Locations\Http\Controllers\StateController;

Route::middleware(['web', 'auth'])->group(function () {

    // Countries
    Route::prefix('panel/locations/countries')
        ->name('locations.countries.')
        ->group(function () {
            Route::get('/', [CountryController::class, 'index'])->name('index');
            Route::get('/create', [CountryController::class, 'create'])->name('create');
            Route::post('/', [CountryController::class, 'store'])->name('store');
            Route::get('/{country}/edit', [CountryController::class, 'edit'])->name('edit');
            Route::put('/{country}', [CountryController::class, 'update'])->name('update');
            Route::delete('/{country}', [CountryController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-action', [CountryController::class, 'bulkAction'])->name('bulk-action');
        });

    // States
    Route::prefix('panel/locations/states')
        ->name('locations.states.')
        ->group(function () {
            Route::get('/', [StateController::class, 'index'])->name('index');
            Route::get('/create', [StateController::class, 'create'])->name('create');
            Route::post('/', [StateController::class, 'store'])->name('store');
            Route::get('/{state}/edit', [StateController::class, 'edit'])->name('edit');
            Route::put('/{state}', [StateController::class, 'update'])->name('update');
            Route::delete('/{state}', [StateController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-action', [StateController::class, 'bulkAction'])->name('bulk-action');
        });

    // Cities
    Route::prefix('panel/locations/cities')
        ->name('locations.cities.')
        ->group(function () {
            Route::get('/', [CityController::class, 'index'])->name('index');
            Route::get('/create', [CityController::class, 'create'])->name('create');
            Route::post('/', [CityController::class, 'store'])->name('store');
            Route::get('/{city}/edit', [CityController::class, 'edit'])->name('edit');
            Route::put('/{city}', [CityController::class, 'update'])->name('update');
            Route::delete('/{city}', [CityController::class, 'destroy'])->name('destroy');
            Route::post('/bulk-action', [CityController::class, 'bulkAction'])->name('bulk-action');
        });

    // Import / Export
    Route::prefix('panel/locations')
        ->name('locations.')
        ->group(function () {
            Route::get('/import', [LocationImportController::class, 'index'])->name('import');
            Route::post('/import', [LocationImportController::class, 'import'])->name('import.store');
            Route::get('/export', [LocationImportController::class, 'export'])->name('export');
        });
});
