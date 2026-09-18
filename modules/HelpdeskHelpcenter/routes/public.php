<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskHelpcenter\Http\Controllers\Api\ArticleVoteController;
use Modules\HelpdeskHelpcenter\Http\Controllers\HelpCenterPublicController;
use Modules\HelpdeskHelpcenter\Http\Controllers\SitemapController;

Route::middleware(['web', 'throttle:60,1'])
    ->prefix('helpcenter')
    ->name('public.helpcenter.')
    ->group(function () {
        Route::get('/', [HelpCenterPublicController::class, 'index'])->name('index');
        Route::get('search', [HelpCenterPublicController::class, 'search'])->name('search');
        Route::get('articles/{slug}', [HelpCenterPublicController::class, 'show'])->name('show');
        Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap')->withoutMiddleware('throttle:60,1');
    });

Route::middleware(['api', 'throttle:5,60'])
    ->prefix('api/helpcenter')
    ->name('api.helpcenter.')
    ->group(function () {
        Route::post('articles/{slug}/vote', [ArticleVoteController::class, 'vote'])->name('articles.vote');
    });
