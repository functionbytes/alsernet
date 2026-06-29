<?php

use Illuminate\Support\Facades\Route;
use Modules\Engagement\Http\Controllers\Api\Sdk\AbTestController as SdkAbTestController;
use Modules\Engagement\Http\Controllers\Api\Sdk\AssetProxyController;
use Modules\Engagement\Http\Controllers\Api\Sdk\CatalogController;
use Modules\Engagement\Http\Controllers\Api\Sdk\ContextController;
use Modules\Engagement\Http\Controllers\Api\Sdk\GdprController;
use Modules\Engagement\Http\Controllers\Api\Sdk\IdentifyController;
use Modules\Engagement\Http\Controllers\Api\Sdk\InitController;
use Modules\Engagement\Http\Controllers\Api\Sdk\MlController;
use Modules\Engagement\Http\Controllers\Api\Sdk\MobileDeviceController;
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
    ->middleware([EnsureWebsiteToken::class, 'throttle:engagement-sdk'])
    ->group(function () {
        Route::post('init', InitController::class)->name('init');
        Route::post('identify', IdentifyController::class)->name('identify');
        Route::post('track', TrackController::class)->name('track');
        Route::post('context', ContextController::class)->name('context');
        Route::get('triggers', TriggerController::class)->name('triggers');
        Route::get('personalizations', PersonalizationController::class)->name('personalizations');
        Route::get('recommendations', RecommendationController::class)->name('recommendations');
        Route::post('catalog/sync', [CatalogController::class, 'sync'])->name('catalog.sync')->middleware('throttle:10,1');
        Route::get('ab-tests', SdkAbTestController::class)->name('ab-tests');

        Route::prefix('mobile')->name('mobile.')->group(function () {
            Route::post('register', [MobileDeviceController::class, 'register'])->name('register');
            Route::post('preferences', [MobileDeviceController::class, 'updatePreferences'])->name('preferences');
            Route::post('unregister', [MobileDeviceController::class, 'unregister'])->name('unregister');
        });

        Route::prefix('ml')->name('ml.')->group(function () {
            Route::get('conversion', [MlController::class, 'predictConversion'])->name('conversion');
            Route::get('next-action', [MlController::class, 'predictNextAction'])->name('next-action');
        });

        // GDPR compliance: data export (Art. 15) and erasure (Art. 17).
        // TODO: Reinforce auth with HMAC signature from the PS/Shopify plugin payload
        //       in addition to the website_token already verified by EnsureWebsiteToken.
        Route::prefix('gdpr')->name('gdpr.')->group(function () {
            Route::post('export', [GdprController::class, 'export'])->name('export');
            Route::post('delete', [GdprController::class, 'delete'])->name('delete');
        });
    });

Route::post('sdk/webhook/{platform}/{integrationId}', PlatformWebhookController::class)
    ->where('platform', 'prestashop|shopify|woocommerce|custom')
    ->middleware('throttle:120,1')
    ->name('sdk.webhook');

Route::get('assets/{bundle}', AssetProxyController::class)
    ->where('bundle', '[a-z0-9-]+')
    ->name('assets');

// Vite-emitted lazy chunks for the livechat widget. The widget resolves them
// relative to its own URL, so when served from /eng/api/assets/livechat-widget
// the browser requests /eng/api/assets/chunks/<hashed-file>.js.
Route::get('assets/chunks/{file}', [AssetProxyController::class, 'chunk'])
    ->where('file', '[A-Za-z0-9_.-]+\.(js|css)')
    ->name('assets.chunk');
