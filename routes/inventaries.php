<?php

use App\Http\Controllers\Inventaries\Products\BarcodeController as ProductsBarcodesController;
use App\Http\Controllers\Inventaries\Locations\BarcodeController as LocationsBarcodesController;
use App\Http\Controllers\Inventaries\Shops\Locations\LocationsController as ShopsLocationsController;
use App\Http\Controllers\Inventaries\Inventaries\InventariesController;
use App\Http\Controllers\Inventaries\Inventaries\ReportsController;
use App\Http\Controllers\Inventaries\Inventaries\ResumenController;
use App\Http\Controllers\Inventaries\Shops\Shops\ShopsController;
use App\Http\Controllers\Inventaries\DashboardController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Inventaries\Inventaries\LocationsController as InventariesLocationsController;

Route::group(['prefix' => 'inventarie', 'middleware' => ['auth', 'roles:inventaries']], function () {

    Route::get('/', [InventariesController::class, 'index'])->name('inventarie.dashboard');

    Route::group(['prefix' => 'inventaries'], function () {

        Route::get('/', [InventariesController::class, 'index'])->name('inventarie.inventaries');
        Route::get('/create', [InventariesController::class, 'create'])->name('inventarie.inventarie.create');
        Route::post('/update', [InventariesController::class, 'update'])->name('inventarie.inventarie.update');
        Route::get('/edit/{slack}', [InventariesController::class, 'edit'])->name('inventarie.inventarie.edit');
        Route::get('/view/{slack}', [InventariesController::class, 'view'])->name('inventarie.inventarie.view');
        Route::get('/destroy/{slack}', [InventariesController::class, 'destroy'])->name('inventarie.inventarie.destroy');
        Route::get('/report/{slack}', [InventariesController::class, 'report'])->name('inventarie.inventarie.report');

        Route::get('/close/{slack}', [InventariesController::class, 'close'])->name('inventarie.inventarie.close');
        Route::get('/arrange/{slack}', [InventariesController::class, 'arrange'])->name('inventarie.inventarie.arrange');
        Route::get('/content/{slack}', [InventariesController::class, 'content'])->name('inventarie.inventarie.content');
        Route::get('/report/{slack}', [InventariesController::class, 'report'])->name('inventarie.inventarie.report');

        Route::post('/locations/close', [InventariesLocationsController::class, 'close'])->name('inventarie.inventarie.location.close');

        Route::post('/locations/validate/location', [InventariesLocationsController::class, 'validateLocation'])->name('inventarie.inventarie.location.validate.location');
        Route::post('/locations/validate/location/genrate', [InventariesLocationsController::class, 'validateGenerate'])->name('inventarie.inventarie.location.validate.validate');
        Route::post('/locations/validate/product', [InventariesLocationsController::class, 'validateProduct'])->name('inventarie.inventarie.location.validate.product');

        Route::get('/locations/content/{slack}', [InventariesLocationsController::class, 'location'])->name('inventarie.inventarie.location');

        Route::get('/locations/modalitie/{location}', [InventariesLocationsController::class, 'modalitie'])->name('inventarie.inventarie.location.modalitie');
        Route::get('/locations/modalitie/automatic/{location}', [InventariesLocationsController::class, 'automatic'])->name('inventarie.inventarie.location.automatic');
        Route::get('/locations/modalitie/manual/{location}', [InventariesLocationsController::class, 'manual'])->name('inventarie.inventarie.location.manual');

        Route::get('/locations/modalitie/{location}/{inventarie}', [InventariesLocationsController::class, 'location'])->name('inventarie.inventarie.location.validate.modalitie');


    });

    Route::group(['prefix' => 'locations'], function () {
        Route::get('/all/barcode', [LocationsBarcodesController::class, 'all'])->name('manager.shops.locations.barcodes.all');
        Route::get('/single/barcode/{slack}', [LocationsBarcodesController::class, 'single'])->name('manager.shops.locations.barcodes.single');
    });


    Route::group(['prefix' => 'products'], function () {
        Route::get('/all/barcode', [ProductsBarcodesController::class, 'all'])->name('manager.products.barcodes.all');
        Route::get('/single/barcode/{slack}', [ProductsBarcodesController::class, 'single'])->name('manager.products.barcodes.single');
    });

    Route::group(['prefix' => 'settings'], function () {
       // Route::get('/', [SettingsController::class, 'index'])->name('inventarie.settings');
        //Route::post('/update', [SettingsController::class, 'update'])->name('inventarie.settings.update');
    });

});
