<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskIntegration\Http\Controllers\Managers\CustomerIntegrationsController;

// show()/auditLog() quedan FUERA del gate: degradan a payload vacio dentro
// del propio servicio (CustomerIntegrationService::buildPayload()) para que
// la ficha 360 del cliente nunca rompa con el toggle apagado — cubierto por
// CustomerIntegrationsIntegrationToggleTest.
Route::get('/customers/{customer}/integrations', [CustomerIntegrationsController::class, 'show'])
    ->middleware('throttle:helpdeskintegration-show')
    ->name('manager.helpdesk.customers.integrations.show');

Route::get('/customers/{customer}/integrations/audit-log', [CustomerIntegrationsController::class, 'auditLog'])
    ->middleware('throttle:helpdeskintegration-audit')
    ->name('manager.helpdesk.customers.integrations.audit-log');

// Gate de un solo punto para el resto del grupo: helpdesk_integration_enabled()
// (modulo instalado + toggle admin) — antes ningun metodo de
// CustomerIntegrationsController comprobaba el toggle, asi que
// link/unlink/sync/requestIdentity seguian alcanzables con la integracion
// apagada desde Settings.
Route::middleware('integration.enabled:integration')->group(function () {
    Route::post('/customers/{customer}/integrations/sync', [CustomerIntegrationsController::class, 'sync'])
        ->middleware('throttle:helpdeskintegration-sync')
        ->name('manager.helpdesk.customers.integrations.sync');

    Route::post('/customers/{customer}/integrations/{platform}/sync', [CustomerIntegrationsController::class, 'syncPlatform'])
        ->middleware('throttle:helpdeskintegration-sync')
        ->name('manager.helpdesk.customers.integrations.sync-platform');

    Route::get('/customers/{customer}/integrations/search', [CustomerIntegrationsController::class, 'search'])
        ->middleware('throttle:helpdeskintegration-search')
        ->name('manager.helpdesk.customers.integrations.search');

    Route::get('/customers/{customer}/integrations/{platform}/detail', [CustomerIntegrationsController::class, 'detail'])
        ->middleware('throttle:helpdeskintegration-search')
        ->name('manager.helpdesk.customers.integrations.detail');

    Route::post('/customers/{customer}/integrations/link', [CustomerIntegrationsController::class, 'link'])
        ->middleware('throttle:helpdeskintegration-link')
        ->name('manager.helpdesk.customers.integrations.link');

    Route::post('/customers/{customer}/integrations/unlink', [CustomerIntegrationsController::class, 'unlink'])
        ->middleware('throttle:helpdeskintegration-link')
        ->name('manager.helpdesk.customers.integrations.unlink');

    // Gate de verificacion de identidad del cliente (OTP email/SMS).
    // El throttle de ruta es un backstop amplio por IP; el limite fino y con
    // mensaje propio (5 codigos / 10 min por cliente) vive en el controller via
    // AuthRateLimiter (scope customer_identity_request) — debe quedar POR ENCIMA
    // de ese umbral para que el mensaje personalizado dispare primero.
    Route::post('/customers/{customer}/identity/request', [CustomerIntegrationsController::class, 'requestIdentity'])
        ->middleware('throttle:helpdeskintegration-identity-request')
        ->name('manager.helpdesk.customers.identity.request');

    Route::post('/customers/{customer}/identity/verify', [CustomerIntegrationsController::class, 'verifyIdentity'])
        ->middleware('throttle:helpdeskintegration-identity-verify')
        ->name('manager.helpdesk.customers.identity.verify');

    Route::post('/customers/{customer}/identity/verify-manual', [CustomerIntegrationsController::class, 'verifyManual'])
        ->middleware('throttle:helpdeskintegration-identity-verify')
        ->name('manager.helpdesk.customers.identity.verify-manual');
});
