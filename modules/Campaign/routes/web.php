<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\Campaign\Http\Controllers\Public\HealthController;
use Modules\Campaign\Http\Controllers\Public\SubscriptionController;
use Modules\Campaign\Http\Controllers\Public\TrackingController;

/*
|--------------------------------------------------------------------------
| Campaign — rutas públicas (sin auth)
|--------------------------------------------------------------------------
| Cargadas por CampaignServiceProvider con prefijo `campaign` y middleware
| `web` (sin auth — los endpoints son los que llaman los emails enviados).
*/

Route::prefix('track')->as('campaign.track.')->group(function () {
    Route::get('open/{messageId}.png', [TrackingController::class, 'open'])->name('open');
    Route::get('click/{messageId}/{linkHash}', [TrackingController::class, 'click'])->name('click');

    // Unsubscribe acepta GET (clic en link) y POST (one-click RFC 8058)
    Route::match(['get', 'post'], 'unsubscribe/{subscriberUid}/{messageId}', [TrackingController::class, 'unsubscribe'])
        ->name('unsubscribe')
        ->withoutMiddleware('csrf')
        ->withoutMiddleware(VerifyCsrfToken::class);
});

// Subscription pages públicas (formulario embebido, doble opt-in, manage)
Route::get('subscribe/{listUid}', [SubscriptionController::class, 'form'])->name('campaign.subscribe.form');
Route::post('subscribe/{listUid}', [SubscriptionController::class, 'subscribe'])->name('campaign.subscribe.submit');
Route::get('subscribe/{listUid}/thanks', [SubscriptionController::class, 'thanks'])->name('campaign.subscribe.thanks');
Route::get('confirm/{token}', [SubscriptionController::class, 'confirm'])->name('campaign.subscribe.confirm');
Route::get('manage/{subUid}', [SubscriptionController::class, 'manage'])->name('campaign.manage');
Route::post('manage/{subUid}', [SubscriptionController::class, 'updatePreferences'])->name('campaign.manage.update');

// Health check para monitoring (sin auth)
Route::get('health', [HealthController::class, 'check'])->name('campaign.health');
Route::get('health/simple', [HealthController::class, 'simple'])->name('campaign.health.simple');
