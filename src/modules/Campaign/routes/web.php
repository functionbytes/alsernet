<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\Campaign\Http\Controllers\Public\DemoLoginController;
use Modules\Campaign\Http\Controllers\Public\HealthController;
use Modules\Campaign\Http\Controllers\Public\ProviderWebhookController;
use Modules\Campaign\Http\Controllers\Public\SubscriptionController;
use Modules\Campaign\Http\Controllers\Public\TrackingController;
use Modules\Campaign\Http\Middleware\TrackRateLimit;

/*
|--------------------------------------------------------------------------
| Campaign — rutas públicas (sin auth)
|--------------------------------------------------------------------------
| Cargadas por CampaignServiceProvider con prefijo `campaign` y middleware
| `web` (sin auth — los endpoints son los que llaman los emails enviados).
*/

Route::prefix('track')->as('campaign.track.')->middleware(TrackRateLimit::class)->group(function () {
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

// Magic link para demo: auto-login con URL firmada (1h validez)
Route::get('demo-login', [DemoLoginController::class, 'login'])
    ->name('campaign.demo-login');

// Provider inbound webhooks (bounce/delivery/complaint)
Route::post('webhooks/sendgrid/{serverUid}', [ProviderWebhookController::class, 'sendgrid'])
    ->name('campaign.webhooks.sendgrid')
    ->withoutMiddleware('csrf')
    ->withoutMiddleware(VerifyCsrfToken::class);

Route::post('webhooks/mailgun/{serverUid}', [ProviderWebhookController::class, 'mailgun'])
    ->name('campaign.webhooks.mailgun')
    ->withoutMiddleware('csrf')
    ->withoutMiddleware(VerifyCsrfToken::class);

Route::post('webhooks/ses/{serverUid}', [ProviderWebhookController::class, 'ses'])
    ->name('campaign.webhooks.ses')
    ->withoutMiddleware('csrf')
    ->withoutMiddleware(VerifyCsrfToken::class);

Route::post('webhooks/postmark/{serverUid}', [ProviderWebhookController::class, 'postmark'])
    ->name('campaign.webhooks.postmark')
    ->withoutMiddleware('csrf')
    ->withoutMiddleware(VerifyCsrfToken::class);
