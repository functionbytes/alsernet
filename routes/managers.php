<?php

use App\Http\Controllers\Managers\DashboardController;
use App\Http\Controllers\Managers\Inventaries\HistoryController;
use App\Http\Controllers\Managers\Inventaries\ReportsController;
use App\Http\Controllers\Managers\Inventaries\ResumenController;
use App\Http\Controllers\Managers\Inventaries\InventariesController;
use App\Http\Controllers\Managers\Inventaries\LocationsController as InventariesLocationsController;
use App\Http\Controllers\Managers\Products\BarcodeController as ProductsBarcodesController;
use App\Http\Controllers\Managers\Products\ProductsController;
use App\Http\Controllers\Managers\Settings\SettingsController;
use App\Http\Controllers\Managers\Shops\Locations\BarcodeController as LocationsBarcodesController;
use App\Http\Controllers\Managers\Shops\Locations\LocationsController;
use App\Http\Controllers\Managers\Shops\Locations\LocationsController as ShopsLocationsController;
use App\Http\Controllers\Managers\Shops\Shops\ShopsController;
use App\Http\Controllers\Managers\Users\UsersController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'manager', 'middleware' => ['auth','manager']], function () {

    Route::get('/', [DashboardController::class, 'dashboard'])->name('manager.dashboard');

    Route::group(['prefix' => 'shops'], function () {

        Route::get('/', [ShopsController::class, 'index'])->name('manager.shops');
        Route::get('/create', [ShopsController::class, 'create'])->name('manager.shops.create');
        Route::post('/update', [ShopsController::class, 'update'])->name('manager.shops.update');
        Route::get('/edit/{slack}', [ShopsController::class, 'edit'])->name('manager.shops.edit');
        Route::get('/view/{slack}', [ShopsController::class, 'view'])->name('manager.shops.view');
        Route::get('/destroy/{slack}', [ShopsController::class, 'destroy'])->name('manager.shops.destroy');


        Route::get('/locations/{slack}', [ShopsLocationsController::class, 'index'])->name('manager.shops.locations');
        Route::get('/locations/all/barcode', [LocationsBarcodesController::class, 'index'])->name('manager.shops.locations.barcodes.all');
        Route::get('/locations/create', [ShopsLocationsController::class, 'create'])->name('manager.shops.locations.create');
        Route::post('/locations/update', [ShopsLocationsController::class, 'update'])->name('manager.shops.locations.update');
        Route::get('/locations/edit/{slack}', [ShopsLocationsController::class, 'edit'])->name('manager.shops.locations.edit');
        Route::get('/locations/view/{slack}', [ShopsLocationsController::class, 'view'])->name('manager.shops.locations.view');
        Route::get('/locations/exists/{slack}', [ShopsLocationsController::class, 'exists'])->name('manager.shops.locations.exists');
        Route::get('/locations/destroy/{slack}', [ShopsLocationsController::class, 'destroy'])->name('manager.shops.locations.destroy');
        Route::get('/locations/single/barcode/{slack}', [LocationsBarcodesController::class, 'destroy'])->name('manager.shops.locations.barcodes.single');

        Route::post('/locations/exists/validate', [ShopsLocationsController::class, 'validate'])->name('manager.shops.locations.exists.validate');

    });

    Route::group(['prefix' => 'products'], function () {

        Route::get('/validate', [ProductsController::class, 'validate'])->name('manager.products');

        Route::get('/', [ProductsController::class, 'index'])->name('manager.products');
        Route::get('/all/barcode', [ProductsBarcodesController::class, 'index'])->name('manager.products.barcodes.all');
        Route::get('/create', [ProductsController::class, 'create'])->name('manager.products.create');
        Route::post('/update', [ProductsController::class, 'update'])->name('manager.products.update');
        Route::get('/edit/{slack}', [ProductsController::class, 'edit'])->name('manager.products.edit');
        Route::get('/view/{slack}', [ProductsController::class, 'view'])->name('manager.locations.view');
        Route::get('/destroy/{slack}', [ProductsController::class, 'destroy'])->name('manager.products.destroy');
        Route::get('/single/barcode/{slack}', [ProductsBarcodesController::class, 'destroy'])->name('manager.products.barcodes.single');
    });


    Route::group(['prefix' => 'inventaries'], function () {
        Route::get('/', [InventariesController::class, 'index'])->name('manager.inventaries');
        Route::get('/create', [InventariesController::class, 'create'])->name('manager.inventaries.create');
        Route::post('/update', [InventariesController::class, 'update'])->name('manager.inventaries.update');
        Route::get('/edit/{slack}', [InventariesController::class, 'edit'])->name('manager.inventaries.edit');
        Route::get('/view/{slack}', [InventariesController::class, 'view'])->name('manager.inventaries.view');
        Route::get('/destroy/{slack}', [InventariesController::class, 'destroy'])->name('manager.inventaries.destroy');
        Route::get('/report/{slack}', [InventariesController::class, 'report'])->name('manager.inventaries.report');

        Route::get('/historys', [HistoryController::class, 'index'])->name('manager.inventaries.historys');
        Route::get('/history/edit/{slack}', [HistoryController::class, 'edit'])->name('manager.historys.edit');
        Route::get('/history/destroy/{slack}', [HistoryController::class, 'destroy'])->name('manager.historys.destroy');

        Route::get('/historys/locations/{slack}', [InventariesLocationsController::class, 'index'])->name('manager.inventaries.locations');
        Route::get('/history/locations/details/{slack}', [InventariesLocationsController::class, 'details'])->name('manager.inventaries.locations.details');
        Route::get('/history/locations/destroy/{slack}', [InventariesLocationsController::class, 'destroy'])->name('manager.inventaries.locations.destroy');
    });


    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [SettingsController::class, 'index'])->name('manager.settings');
        Route::post('/update', [SettingsController::class, 'update'])->name('manager.settings.update');

    });


    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UsersController::class, 'index'])->name('manager.users');
        Route::get('/create', [UsersController::class, 'create'])->name('manager.users.create');
        Route::post('/store', [UsersController::class, 'store'])->name('manager.users.store');
        Route::post('/update', [UsersController::class, 'update'])->name('manager.users.update');
        Route::get('/edit/{slack}', [UsersController::class, 'edit'])->name('manager.users.edit');
        Route::get('/view/{slack}', [UsersController::class, 'view'])->name('manager.users.view');
        Route::get('/destroy/{slack}', [UsersController::class, 'destroy'])->name('manager.users.destroy');

    });

});
