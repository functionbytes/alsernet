<?php

use Illuminate\Support\Facades\Route;
use Modules\Page\Http\Controllers\PageController;
use Modules\Page\Http\Controllers\PageSettingsController;
use Modules\Page\Http\Controllers\PageVersionController;
use Modules\Page\Http\Controllers\PreviewController;
use Modules\Page\Http\Controllers\PublicController;

/*
|--------------------------------------------------------------------------
| Admin Routes (authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('pages/create', [PageController::class, 'create'])->name('pages.create');
    Route::get('settings/pages', [PageSettingsController::class, 'edit'])->name('settings.pages');
    Route::put('settings/pages', [PageSettingsController::class, 'update'])->name('settings.pages.update');
    Route::post('ajax/pages/slug', [PageController::class, 'ajaxSlug'])->name('pages.ajax.slug');
    Route::post('pages', [PageController::class, 'store'])->name('pages.store');
    Route::get('pages/{page}', [PageController::class, 'show'])->name('pages.show');
    Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
    Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
    Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

    Route::post('pages/{page}/publish', [PageController::class, 'publish'])->name('pages.publish');
    Route::post('pages/{page}/unpublish', [PageController::class, 'unpublish'])->name('pages.unpublish');
    Route::post('pages/{page}/duplicate', [PageController::class, 'duplicate'])->name('pages.duplicate');
    Route::post('pages/{id}/restore', [PageController::class, 'restore'])->name('pages.restore');
    Route::delete('pages/{id}/force-delete', [PageController::class, 'forceDelete'])->name('pages.force-delete');

    Route::prefix('pages/{page}/versions')->name('pages.versions.')->group(function () {
        Route::get('/', [PageVersionController::class, 'index'])->name('index');
        Route::post('/create', [PageVersionController::class, 'create'])->name('create');
        Route::get('/compare', [PageVersionController::class, 'compare'])->name('compare');
        Route::get('/{version}', [PageVersionController::class, 'show'])->name('show');
        Route::post('/{version}/restore', [PageVersionController::class, 'restore'])->name('restore');
        Route::delete('/{version}', [PageVersionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('pages/{page}/preview')->name('pages.preview.')->group(function () {
        Route::get('/', [PreviewController::class, 'index'])->name('index');
        Route::post('/generate', [PreviewController::class, 'generate'])->name('generate');
        Route::post('/revoke', [PreviewController::class, 'revoke'])->name('revoke');
    });
});

Route::get('/preview/{slug}/{token}', [PreviewController::class, 'show'])->name('page.preview')->where('slug', '[a-z0-9-]+')->where('token', '[a-zA-Z0-9]{64}');
Route::get('/{path}', [PublicController::class, 'show'])->name('page.show')->where('path', '^(?!dashboard|login|logout|register|password|settings|manager|setting|api|up|broadcasting|pages|preview|pqrsf|attentions|media)([a-z0-9\-\/]+)$');
