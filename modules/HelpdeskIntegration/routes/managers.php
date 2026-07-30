<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskIntegration\Http\Controllers\Managers\CustomerIntegrationsController;

// Movidas desde Modules\Helpdesk\routes\managers.php — mismos nombres de ruta
// y mismo throttling para no romper el JS del modal ni otras referencias existentes.
Route::get('/customers/{customer}/integrations', [CustomerIntegrationsController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('manager.helpdesk.customers.integrations.show');

Route::post('/customers/{customer}/integrations/sync', [CustomerIntegrationsController::class, 'sync'])
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.customers.integrations.sync');

Route::post('/customers/{customer}/integrations/{platform}/sync', [CustomerIntegrationsController::class, 'syncPlatform'])
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.customers.integrations.sync-platform');

Route::get('/customers/{customer}/integrations/search', [CustomerIntegrationsController::class, 'search'])
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.customers.integrations.search');

Route::get('/customers/{customer}/integrations/audit-log', [CustomerIntegrationsController::class, 'auditLog'])
    ->middleware('throttle:30,1')
    ->name('manager.helpdesk.customers.integrations.audit-log');

Route::post('/customers/{customer}/integrations/link', [CustomerIntegrationsController::class, 'link'])
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.customers.integrations.link');

Route::post('/customers/{customer}/integrations/unlink', [CustomerIntegrationsController::class, 'unlink'])
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.customers.integrations.unlink');

// Gate de verificacion de identidad del cliente (OTP email/SMS).
// El throttle de ruta es un backstop amplio por IP; el limite fino y con
// mensaje propio (5 codigos / 10 min por cliente) vive en el controller via
// AuthRateLimiter (scope customer_identity_request) — debe quedar POR ENCIMA
// de ese umbral para que el mensaje personalizado dispare primero.
Route::post('/customers/{customer}/identity/request', [CustomerIntegrationsController::class, 'requestIdentity'])
    ->middleware('throttle:20,1')
    ->name('manager.helpdesk.customers.identity.request');

Route::post('/customers/{customer}/identity/verify', [CustomerIntegrationsController::class, 'verifyIdentity'])
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.customers.identity.verify');

Route::post('/customers/{customer}/identity/verify-manual', [CustomerIntegrationsController::class, 'verifyManual'])
    ->middleware('throttle:10,1')
    ->name('manager.helpdesk.customers.identity.verify-manual');
