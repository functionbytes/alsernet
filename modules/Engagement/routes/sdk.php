<?php

use Illuminate\Support\Facades\Route;
use Modules\Engagement\Http\Controllers\Api\Sdk\AssetProxyController;
use Modules\Engagement\Http\Controllers\Api\Sdk\CatalogController;
use Modules\Engagement\Http\Controllers\Api\Sdk\ContextController;
use Modules\Engagement\Http\Controllers\Api\Sdk\IdentifyController;
use Modules\Engagement\Http\Controllers\Api\Sdk\InitController;
use Modules\Engagement\Http\Controllers\Api\Sdk\PersonalizationController;
use Modules\Engagement\Http\Controllers\Api\Sdk\PlatformWebhookController;
use Modules\Engagement\Http\Controllers\Api\Sdk\RecommendationController;
use Modules\Engagement\Http\Controllers\Api\Sdk\TrackController;
use Modules\Engagement\Http\Controllers\Api\Sdk\TriggerController;
use Modules\Engagement\Http\Middleware\EnsureWebsiteToken;

/*
|--------------------------------------------------------------------------
| Engagement SDK API
|--------------------------------------------------------------------------
| Mounted by EngagementServiceProvider with prefix `eng/api` and name
| `engagement.sdk.`. Public — uses website_token (HMAC for webhooks).
*/

Route::prefix('sdk')
    ->name('sdk.')
    ->middleware(EnsureWebsiteToken::class)
    ->group(function () {
        Route::post('init', InitController::class)->name('init');
        Route::post('identify', IdentifyController::class)->name('identify');
        Route::post('track', TrackController::class)->name('track');
        Route::post('context', ContextController::class)->name('context');
        Route::get('triggers', TriggerController::class)->name('triggers');
        Route::get('personalizations', PersonalizationController::class)->name('personalizations');
        Route::get('recommendations', RecommendationController::class)->name('recommendations');
        Route::post('catalog/sync', [CatalogController::class, 'sync'])->name('catalog.sync')->middleware('throttle:10,1');
    });

Route::post('sdk/webhook/{platform}/{integrationId}', PlatformWebhookController::class)
    ->where('platform', 'prestashop|shopify|woocommerce|custom')
    ->middleware('throttle:120,1')
    ->name('sdk.webhook');

Route::get('assets/{bundle}', AssetProxyController::class)
    ->where('bundle', '[a-z0-9-]+')
    ->name('assets');
