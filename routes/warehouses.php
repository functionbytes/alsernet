<?php

use App\Http\Controllers\warehouses\Locations\BarcodeController as LocationsBarcodesController;
use App\Http\Controllers\warehouses\Locations\TransferController;
use App\Http\Controllers\warehouses\Products\BarcodeController as ProductsBarcodesController;
use App\Http\Controllers\warehouses\Shops\Locations\LocationsController as ShopsLocationsController;
use App\Http\Controllers\warehouses\Shops\Shops\ShopsController;
use App\Http\Controllers\warehouses\Warehouses\LocationsController as WarehousesLocationsController;
use App\Http\Controllers\warehouses\Warehouses\ReportsController;
use App\Http\Controllers\warehouses\Warehouses\ResumenController;
use App\Http\Controllers\warehouses\Warehouses\WarehousesController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'warehouse', 'middleware' => ['auth', 'roles:warehouses']], function () {

    Route::get('/', [WarehousesController::class, 'index'])->name('warehouse.dashboard');

    Route::group(['prefix' => 'warehouses'], function () {

        Route::get('/', [WarehousesController::class, 'index'])->name('warehouse.warehouses');
        Route::get('/create', [WarehousesController::class, 'create'])->name('warehouse.warehouses.create');
        Route::post('/update', [WarehousesController::class, 'update'])->name('warehouse.warehouses.update');
        Route::get('/edit/{slack}', [WarehousesController::class, 'edit'])->name('warehouse.warehouses.edit');
        Route::get('/view/{slack}', [WarehousesController::class, 'view'])->name('warehouse.warehouses.view');
        Route::get('/destroy/{slack}', [WarehousesController::class, 'destroy'])->name('warehouse.warehouses.destroy');
        Route::get('/report/{slack}', [WarehousesController::class, 'report'])->name('warehouse.warehouses.report');

        Route::get('/close/{slack}', [WarehousesController::class, 'close'])->name('warehouse.warehouses.close');
        Route::get('/arrange/{slack}', [WarehousesController::class, 'arrange'])->name('warehouse.warehouses.arrange');
        Route::get('/content/{slack}', [WarehousesController::class, 'content'])->name('warehouse.warehouses.content');
        Route::get('/report/{slack}', [WarehousesController::class, 'report'])->name('warehouse.warehouses.report');

        Route::post('/locations/close', [WarehousesLocationsController::class, 'close'])->name('warehouse.warehouses.location.close');

        Route::post('/locations/validate/location', [WarehousesLocationsController::class, 'validateLocation'])->name('warehouse.warehouses.location.validate.location');
        Route::post('/locations/validate/section', [WarehousesLocationsController::class, 'validateSection'])->name('warehouse.warehouses.location.validate.section');
        Route::post('/locations/validate/location/genrate', [WarehousesLocationsController::class, 'validateGenerate'])->name('warehouse.warehouses.location.validate.validate');
        Route::post('/locations/validate/product', [WarehousesLocationsController::class, 'validateProduct'])->name('warehouse.warehouses.location.validate.product');

        Route::get('/locations/{warehouse}/{location}/{section}', [WarehousesLocationsController::class, 'section'])->name('warehouse.warehouses.location.location.section');
        Route::get('/locations/{warehouse}/{location}/{section}/modalitie', [WarehousesLocationsController::class, 'modalitie'])->name('warehouse.warehouses.location.modalitie');
        Route::get('/locations/{warehouse}/{location}/{section}/automatic', [WarehousesLocationsController::class, 'automatic'])->name('warehouse.warehouses.location.automatic');
        Route::get('/locations/{warehouse}/{location}/{section}/manual', [WarehousesLocationsController::class, 'manual'])->name('warehouse.warehouses.location.manual');

    });

    Route::group(['prefix' => 'transfer'], function () {
        Route::get('/', [TransferController::class, 'index'])->name('inventories.transfer.index');
        Route::post('/search', [TransferController::class, 'searchProduct'])->name('inventories.transfer.search');
        Route::post('/available-sections', [TransferController::class, 'getAvailableSections'])->name('inventories.transfer.available-sections');
        Route::post('/process', [TransferController::class, 'transfer'])->name('inventories.transfer.process');
        Route::get('/history', [TransferController::class, 'history'])->name('inventories.transfer.history');
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
        // Route::get('/', [SettingsController::class, 'index'])->name('warehouse.settings');
        //Route::post('/update', [SettingsController::class, 'update'])->name('warehouse.settings.update');
    });

});
