<?php

use Illuminate\Support\Facades\Route;
use Modules\Faqs\Http\Controllers\Admin\FaqCategoryController;
use Modules\Faqs\Http\Controllers\Admin\FaqController;
use Modules\Faqs\Http\Controllers\Admin\FaqSettingsController;
use Modules\Faqs\Http\Controllers\FaqPublicController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('panel/faqs')
        ->name('faqs.')
        ->group(function () {
            Route::get('/', [FaqController::class, 'index'])->name('index');
            Route::get('/create', [FaqController::class, 'create'])->name('create');
            Route::post('/', [FaqController::class, 'store'])->name('store');
            Route::get('/{faq}/edit', [FaqController::class, 'edit'])->name('edit');
            Route::put('/{faq}', [FaqController::class, 'update'])->name('update');
            Route::delete('/{faq}', [FaqController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('panel/faq-categories')
        ->name('faqs.categories.')
        ->group(function () {
            Route::get('/', [FaqCategoryController::class, 'index'])->name('index');
            Route::get('/create', [FaqCategoryController::class, 'create'])->name('create');
            Route::post('/', [FaqCategoryController::class, 'store'])->name('store');
            Route::get('/{category}/edit', [FaqCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category}', [FaqCategoryController::class, 'update'])->name('update');
            Route::delete('/{category}', [FaqCategoryController::class, 'destroy'])->name('destroy');
        });

    Route::prefix('panel/settings/faqs')
        ->name('settings.faqs.')
        ->group(function () {
            Route::get('/', [FaqSettingsController::class, 'index'])->name('index');
            Route::patch('/', [FaqSettingsController::class, 'update'])->name('update');
        });
});

Route::middleware(['web'])->group(function () {
    Route::get('/faqs', [FaqPublicController::class, 'index'])->name('faqs.public.index');
});
