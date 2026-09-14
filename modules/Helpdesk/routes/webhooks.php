<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\EmailInboundController;
use Modules\Helpdesk\Http\Controllers\Webhooks\FacebookWebhookController;
use Modules\Helpdesk\Http\Controllers\Webhooks\InstagramWebhookController;
use Modules\Helpdesk\Http\Controllers\Webhooks\WhatsAppWebhookController;

// These routes are public (no auth) — Meta webhook verification and delivery require open access.
// CSRF is already excluded via VerifyCsrfToken::$except = ['webhooks/*'].

// Email inbound webhook — supports mailgun, sendgrid, postmark, generic
Route::post('webhooks/helpdesk/email/{provider}', [EmailInboundController::class, 'handle'])
    ->name('helpdesk.webhooks.email-inbound')
    ->middleware('throttle:helpdesk-webhook-inbound');

Route::middleware('throttle:300,1')->prefix('webhooks')->name('webhooks.')->group(function () {
    // WhatsApp Business API
    Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('whatsapp.verify');
    Route::post('whatsapp', [WhatsAppWebhookController::class, 'handle'])->name('whatsapp.handle');

    // Facebook Messenger
    Route::get('facebook', [FacebookWebhookController::class, 'verify'])->name('facebook.verify');
    Route::post('facebook', [FacebookWebhookController::class, 'handle'])->name('facebook.handle');

    // Instagram DMs
    Route::get('instagram', [InstagramWebhookController::class, 'verify'])->name('instagram.verify');
    Route::post('instagram', [InstagramWebhookController::class, 'handle'])->name('instagram.handle');
});
