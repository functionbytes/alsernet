<?php

use Illuminate\Support\Facades\Route;
use Modules\Locales\Http\Controllers\LocaleController;
use Modules\Locales\Http\Controllers\ThemeTranslationController;

// 29-sep-2026: antes solo 'auth' (cualquier usuario del panel). Permisos locale.* del
// LocalesPermissionsSeeder y {locale} restringido a un código ISO (evita '..' en rutas de fichero).
Route::middleware(['auth', 'can:locale.view'])->prefix('panel/settings')->group(function () {

    Route::prefix('locales')->name('locales.')->group(function () {
        Route::get('/', [LocaleController::class, 'index'])->name('index');
        Route::patch('/config', [LocaleController::class, 'storeSettings'])->name('config')->middleware('can:locale.update');
        Route::post('/bulk-action', [LocaleController::class, 'bulkAction'])->name('bulk-action')->middleware('can:locale.update');
        Route::get('/create', [LocaleController::class, 'create'])->name('create')->middleware('can:locale.create');
        Route::post('/', [LocaleController::class, 'store'])->name('store')->middleware('can:locale.create');
        Route::get('/{locale}/edit', [LocaleController::class, 'edit'])->name('edit')->middleware('can:locale.update');
        Route::put('/{locale}', [LocaleController::class, 'update'])->name('update')->middleware('can:locale.update');
        Route::delete('/{locale}', [LocaleController::class, 'destroy'])->name('destroy')->middleware('can:locale.delete');
        Route::post('/{locale}/default', [LocaleController::class, 'setDefault'])->name('default')->middleware('can:locale.set-default');
        Route::post('/{locale}/toggle', [LocaleController::class, 'toggle'])->name('toggle')->middleware('can:locale.toggle');

        Route::prefix('translations')->name('translations.')
            ->middleware('can:locale.translate')
            ->where(['locale' => '[a-z]{2}(_[A-Z]{2})?', 'group' => '[A-Za-z0-9_.-]+'])
            ->group(function () {
            Route::get('/', [ThemeTranslationController::class, 'index'])->name('index');
            Route::get('/{locale}/{group}/export', [ThemeTranslationController::class, 'export'])->name('export');
            Route::post('/{locale}/{group}/import', [ThemeTranslationController::class, 'import'])->name('import');
            Route::post('/{locale}/{group}/bulk', [ThemeTranslationController::class, 'bulkAction'])->name('bulk-action');
            Route::post('/{locale}/{group}/key', [ThemeTranslationController::class, 'updateKey'])->name('update-key');
            Route::post('/{locale}/{group}/rename', [ThemeTranslationController::class, 'renameKey'])->name('rename-key');
            Route::post('/{locale}/{group}/keys', [ThemeTranslationController::class, 'storeKey'])->name('store-key');
            Route::get('/{locale}/{group}', [ThemeTranslationController::class, 'edit'])->name('edit');
            Route::put('/{locale}/{group}', [ThemeTranslationController::class, 'update'])->name('update');
        });
    });
});
