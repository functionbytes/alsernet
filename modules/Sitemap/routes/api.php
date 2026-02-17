<?php

use Illuminate\Support\Facades\Route;
use modules\Sitemap\Http\Controllers\SitemapController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('sitemaps', SitemapController::class)->names('sitemap');
});
