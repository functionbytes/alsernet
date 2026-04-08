<?php

use Illuminate\Support\Facades\Route;
use Modules\Locales\Http\Controllers\LocaleController;
use Modules\Locales\Http\Controllers\ThemeTranslationController;

Route::middleware(['auth'])->prefix('panel/settings')->group(function () {

    Route::prefix('locales')->name('locales.')->group(function () {
        Route::get('/', [LocaleController::class, 'index'])->name('index');
        Route::post('/bulk-action', [LocaleController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/create', [LocaleController::class, 'create'])->name('create');
        Route::post('/', [LocaleController::class, 'store'])->name('store');
        Route::get('/{locale}/edit', [LocaleController::class, 'edit'])->name('edit');
        Route::put('/{locale}', [LocaleController::class, 'update'])->name('update');
        Route::delete('/{locale}', [LocaleController::class, 'destroy'])->name('destroy');
        Route::post('/{locale}/default', [LocaleController::class, 'setDefault'])->name('default');
        Route::post('/{locale}/toggle', [LocaleController::class, 'toggle'])->name('toggle');

        Route::prefix('translations')->name('translations.')->group(function () {
            Route::get('/', [ThemeTranslationController::class, 'index'])->name('index');
            Route::get('/{locale}/{group}', [ThemeTranslationController::class, 'edit'])->name('edit');
            Route::put('/{locale}/{group}', [ThemeTranslationController::class, 'update'])->name('update');
        });
    });
});
