<?php

use Illuminate\Support\Facades\Route;
use Modules\Sitemap\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| Web Routes - Sitemap
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'throttle:60,1'])->group(function () {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap.index');
    Route::get('/sitemap-pages.xml', [SitemapController::class, 'pages'])->name('sitemap.pages');
    Route::get('/sitemap-posts.xml', [SitemapController::class, 'posts'])->name('sitemap.posts');
    Route::get('/sitemap-index.xml', [SitemapController::class, 'sitemapIndex'])->name('sitemap.sitemap-index');
});
