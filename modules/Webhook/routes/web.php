<?php

use Illuminate\Support\Facades\Route;
use Modules\Webhook\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Operational Routes
|--------------------------------------------------------------------------
|
| Rutas para la gestión operacional de webhooks (día a día)
| Prefix: /webhooks (aplicado por ServiceProvider)
| Name: webhooks.* (aplicado por ServiceProvider)
| Middleware: web, auth, role:super-admin
|
*/

// Listing routes
Route::get('/', [WebhookController::class, 'index'])->name('index');

// Monitoring routes (must be before /{uid} to match correctly)
Route::get('/deliveries', [WebhookController::class, 'deliveries'])->name('deliveries');
Route::get('/events', [WebhookController::class, 'events'])->name('events');

// Detail view (must be last to avoid catching specific routes)
Route::get('/{uid}', [WebhookController::class, 'show'])->name('show');
