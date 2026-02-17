<?php

use Illuminate\Support\Facades\Route;
use Modules\Seo\Http\Controllers\RobotsTxtController;
use Modules\Seo\Http\Controllers\SeoMetaWebController;
use Modules\Seo\Http\Controllers\SeoRedirectController;
use Modules\Seo\Http\Controllers\SitemapAdminController;
use Modules\Seo\Http\Controllers\SitemapController;

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

        Route::get('robots', [RobotsTxtController::class, 'edit'])->name('robots.edit');
        Route::post('robots', [RobotsTxtController::class, 'update'])->name('robots.update');

        Route::get('sitemap', [SitemapAdminController::class, 'index'])->name('sitemap.index');
        Route::post('sitemap/generate', [SitemapAdminController::class, 'generate'])->name('sitemap.generate');
        Route::post('sitemap/clear-cache', [SitemapAdminController::class, 'clearCache'])->name('sitemap.clear-cache');
    });
});

// Public - Robots.txt Service
Route::get('/robots.txt', [RobotsTxtController::class, 'serve'])->middleware('web')->name('robots-txt.serve');

// Public Sitemap Routes
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-posts.xml', [SitemapController::class, 'posts'])->name('sitemap.posts');
Route::get('/sitemap-index.xml', [SitemapController::class, 'sitemapIndex'])->name('sitemap.sitemap-index');
