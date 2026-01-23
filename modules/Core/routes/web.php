<?php

use Illuminate\Routing\Route;
use Modules\Prestashop\Http\Controllers\Settings\CategoryController;
use Modules\System\Http\Controllers\Settings\CategoriesController;
use Modules\System\Http\Controllers\Settings\LangsController;
use Modules\System\Http\Controllers\Settings\LocalizationSettingsController;
use Modules\System\Http\Controllers\Settings\SearchSettingsController;
use Modules\System\Http\Controllers\Settings\SettingsController;
use Modules\System\Http\Controllers\Settings\TranslationController;

Route::group(['prefix' => 'categories'], function () {
    Route::get('/', [CategoriesController::class, 'index'])->name('manager.categories');
    Route::get('/create', [CategoriesController::class, 'create'])->name('manager.categories.create');
    Route::post('/store', [CategoriesController::class, 'store'])->name('manager.categories.store');
    Route::post('/update', [CategoriesController::class, 'update'])->name('manager.categories.update');
    Route::get('/edit/{uid}', [CategoriesController::class, 'edit'])->name('manager.categories.edit');
    Route::get('/view/{uid}', [CategoriesController::class, 'view'])->name('manager.categories.view');
    Route::get('/destroy/{uid}', [CategoriesController::class, 'destroy'])->name('manager.categories.destroy');
});

Route::group(['prefix' => 'langs'], function () {
    Route::get('/', [LangsController::class, 'index'])->name('manager.langs');
    Route::get('/create', [LangsController::class, 'create'])->name('manager.langs.create');
    Route::post('/store', [LangsController::class, 'store'])->name('manager.langs.store');
    Route::post('/update', [LangsController::class, 'update'])->name('manager.langs.update');
    Route::get('/edit/{uid}', [LangsController::class, 'edit'])->name('manager.langs.edit');
    Route::get('/view/{uid}', [LangsController::class, 'view'])->name('manager.langs.view');
    Route::get('/destroy/{uid}', [LangsController::class, 'destroy'])->name('manager.langs.destroy');
    Route::get('/categories', [LangsController::class, 'getCategories'])->name('manager.langs.categories');
});

Route::group(['prefix' => 'backups'], function () {

    Route::get('/', [SettingsController::class, 'index'])->name('settings.settings');
    Route::post('/update', [SettingsController::class, 'update'])->name('settings.settings.update');

    Route::post('/favicon', [SettingsController::class, 'storeFavicon'])->name('settings.settings.favicon');
    Route::get('/delete/favicon/{id}', [SettingsController::class, 'deleteFavicon'])->name('settings.settings.favicon.delete');
    Route::get('/get/favicon/{id}', [SettingsController::class, 'getFavicon'])->name('settings.settings.favicon.get');

    Route::post('/logo', [SettingsController::class, 'storeLogo'])->name('settings.settings.logo');
    Route::get('/delete/logo/{id}', [SettingsController::class, 'deleteLogo'])->name('settings.settings.logo.delete');
    Route::get('/get/logo/{id}', [SettingsController::class, 'getLogo'])->name('settings.settings.logo.get');

    // @deprecated Maintenance routes moved to modules/System/routes/web.php
    // Route::get('/maintenance', [MantenanceSettingsController::class, 'index'])->name('settings.settings.maintenance');
    // Route::post('/maintenance/update', [MantenanceSettingsController::class, 'update'])->name('settings.settings.maintenance.update');

    Route::get('/hours', [HoursSettingsController::class, 'index'])->name('settings.settings.hours');
    Route::post('/hours/update', [HoursSettingsController::class, 'update'])->name('settings.settings.hours.update');

    // Storage management routes
    Route::get('/storage', [StorageController::class, 'index'])->name('settings.settings.storage');
    Route::get('/storage/create', [StorageController::class, 'create'])->name('settings.settings.storage.create');
    Route::post('/storage/update', [StorageController::class, 'update'])->name('settings.settings.storage.update');
    Route::delete('/storage', [StorageController::class, 'destroy'])->name('settings.settings.storage.destroy');
    Route::post('/storage', [StorageController::class, 'store'])->name('settings.settings.storage.store');
    Route::get('/storage/{index}/edit', [StorageController::class, 'edit'])->name('settings.settings.storage.edit');
    Route::patch('/storage/{index}', [StorageController::class, 'updateDisk'])->name('settings.settings.storage.update-disk');

    // Category management routes (hierarchical with PrestaShop sync)
    Route::group(['prefix' => 'categories'], function () {
        Route::get('/', [CategoryController::class, 'index'])->name('settings.settings.categories.index');
        Route::get('/create', [CategoryController::class, 'create'])->name('settings.settings.categories.create');
        Route::post('/', [CategoryController::class, 'store'])->name('settings.settings.categories.store');
        Route::get('/{uid}/edit', [CategoryController::class, 'edit'])->name('settings.settings.categories.edit');
        Route::put('/{uid}', [CategoryController::class, 'update'])->name('settings.settings.categories.update');
        Route::delete('/{uid}', [CategoryController::class, 'destroy'])->name('settings.settings.categories.destroy');
        Route::post('/reorder', [CategoryController::class, 'reorder'])->name('settings.settings.categories.reorder');
        Route::post('/{uid}/sync-to-prestashop', [CategoryController::class, 'syncToPrestaShop'])->name('settings.settings.categories.sync-to-prestashop');
        Route::post('/sync-from-prestashop', [CategoryController::class, 'syncFromPrestaShop'])->name('settings.settings.categories.sync-from-prestashop');
        Route::get('/conflicts', [CategoryController::class, 'conflicts'])->name('settings.settings.categories.conflicts');
        Route::post('/conflicts/{mapping}/resolve', [CategoryController::class, 'resolveConflict'])->name('settings.settings.categories.conflicts.resolve');
    });

    // Search Settings
    Route::group(['prefix' => 'search'], function () {
        Route::get('/', [SearchSettingsController::class, 'index'])->name('settings.settings.search.index');
        Route::put('/update', [SearchSettingsController::class, 'update'])->name('settings.settings.search.update');
    });

    // Localization Settings
    Route::group(['prefix' => 'localization'], function () {
        Route::get('/', [LocalizationSettingsController::class, 'index'])->name('settings.settings.localization.index');
        Route::put('/update', [LocalizationSettingsController::class, 'update'])->name('settings.settings.localization.update');
    });

    Route::group(['prefix' => 'translations'], function () {
        Route::get('/', [TranslationController::class, 'index'])->name('settings.settings.translations.index');
        Route::get('/edit/{locale}/{file}', [TranslationController::class, 'edit'])->name('settings.settings.translations.edit');
        Route::patch('/update/{locale}/{file}', [TranslationController::class, 'update'])->name('settings.settings.translations.update');
    });

});
