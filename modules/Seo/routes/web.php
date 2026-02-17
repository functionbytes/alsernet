<?php

use Illuminate\Support\Facades\Route;
use Modules\Seo\Http\Controllers\RobotsTxtController;
use Modules\Seo\Http\Controllers\SeoMetaWebController;
use Modules\Seo\Http\Controllers\SeoRedirectController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for the Seo module.
|
*/

Route::prefix('setting')->middleware(['web', 'auth'])->name('setting.')->group(function () {
    Route::prefix('seo')->name('seo.')->group(function () {
        Route::resource('metas', SeoMetaWebController::class)->except(['create', 'store']);
        Route::delete('metas-bulk-delete', [SeoMetaWebController::class, 'bulkDelete'])->name('metas.bulk-delete');

        Route::resource('redirects', SeoRedirectController::class);
        Route::patch('redirects/{redirect}/toggle-active', [SeoRedirectController::class, 'toggleActive'])->name('redirects.toggle-active');
        Route::delete('redirects-bulk-delete', [SeoRedirectController::class, 'bulkDelete'])->name('redirects.bulk-delete');
        Route::get('redirects-clear-cache', [SeoRedirectController::class, 'clearCache'])->name('redirects.clear-cache');
    });
});

// Admin - Robots.txt Management
Route::prefix('admin/theme')
    ->middleware(['web', 'auth'])
    ->name('admin.theme.')
    ->group(function () {
        Route::get('/robots-txt', [RobotsTxtController::class, 'edit'])->name('robots-txt');
        Route::post('/robots-txt', [RobotsTxtController::class, 'update'])->name('robots-txt.update');
    });

// Public - Robots.txt Service
Route::get('/robots.txt', [RobotsTxtController::class, 'serve'])->middleware('web')->name('robots-txt.serve');
