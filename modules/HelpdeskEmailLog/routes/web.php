<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailClickTrackingController;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailLogController;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailLogViewsController;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailOpenTrackingController;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailProviderWebhookController;
use Modules\HelpdeskEmailLog\Http\Controllers\EmailReputationController;
use Modules\HelpdeskEmailLog\Http\Controllers\Settings\BounceMailboxesController;
use Modules\HelpdeskEmailLog\Http\Controllers\Settings\EmailLogSettingsController;
use Modules\HelpdeskEmailLog\Http\Controllers\Settings\EmailSuppressionController;

// Pixel de apertura — SIN auth a propósito: lo carga el cliente de correo del
// destinatario, no un usuario logueado del panel. Fuera del prefix
// panel/helpdeskemaillog (que exige auth) y del throttle agresivo del resto
// del módulo (un cliente de correo puede reintentar la carga varias veces).
// throttle:120,1 por IP: generoso para reintentos legítimos de un cliente de
// correo, suficiente para frenar la enumeración/DoS de UIDs desde una sola
// fuente (el UUID en sí ya es el control principal, esto es defensa en
// profundidad — ver auditoría de seguridad de la Fase 0).
Route::middleware('throttle:120,1')
    ->get('/e/{emailLog:uid}.gif', [EmailOpenTrackingController::class, 'pixel'])
    ->whereUuid('emailLog')
    ->name('helpdeskemaillog.pixel');

// Redirección de clic — misma lógica de exposición pública que el píxel de
// arriba: la sigue el navegador/cliente de correo del destinatario al pulsar
// un enlace reescrito (ver LogEmailQueued::injectClickTracking), nunca un
// usuario logueado del panel.
Route::middleware('throttle:120,1')
    ->get('/e/{emailLog:uid}/c/{token}', [EmailClickTrackingController::class, 'redirect'])
    ->whereUuid('emailLog')
    ->where('token', '[A-Za-z0-9]+')
    ->name('helpdeskemaillog.click');

// Webhooks de proveedor — SIN auth (los llama el proveedor externo, no un
// usuario logueado); la autenticidad la valida cada adapter (firma/token
// propio del proveedor), no una sesión. Bajo /webhooks/ a propósito: es el
// prefijo que Modules\Core\Http\Middleware\VerifyCsrfToken ya excluye
// globalmente (mismo patrón que Mailer/HelpdeskSocial) — un POST externo
// nunca trae token CSRF. Throttle generoso: un proveedor real puede reenviar
// ráfagas de eventos.
Route::middleware('throttle:120,1')
    ->post('/webhooks/helpdeskemaillog/{provider}', [EmailProviderWebhookController::class, 'receive'])
    ->name('helpdeskemaillog.webhooks.receive');

// The `web` middleware group is applied by the service provider.
Route::middleware('auth')
    ->prefix('panel/helpdeskemaillog')
    ->name('helpdeskemaillog.')
    ->group(function () {
        Route::get('/', [EmailLogController::class, 'index'])->name('index');

        Route::middleware('throttle:6,1')
            ->get('/export', [EmailLogController::class, 'export'])
            ->name('export');

        // Mismo throttle que /export: exporta SOLO los uids marcados en el
        // listado (checkbox), vía POST porque una selección larga no cabe
        // bien en query string GET (mismo motivo que bulk-resend/bulk-destroy).
        Route::middleware('throttle:6,1')
            ->post('/export-selected', [EmailLogController::class, 'exportSelected'])
            ->name('export-selected');

        Route::prefix('reputation')->name('reputation.')->group(function () {
            Route::get('/', [EmailReputationController::class, 'index'])->name('index');
            Route::post('/refresh', [EmailReputationController::class, 'refresh'])->name('refresh');
        });

        Route::prefix('views')->name('views.')->group(function () {
            Route::get('/', [EmailLogViewsController::class, 'index'])->name('index');
            Route::post('/', [EmailLogViewsController::class, 'store'])->name('store');
            Route::delete('/{view}', [EmailLogViewsController::class, 'destroy'])->name('destroy');
        });

        // Papelera de registros (30 días de recuperación, ver destroy()/
        // bulkDestroy() ahora con SoftDeletes) — literal 'trash' antes del
        // wildcard {emailLog} de abajo, mismo criterio que 'export'/
        // 'reputation'/'views': ese wildcard exige whereUuid, así que
        // 'trash' nunca lo matchearía de todas formas, pero se declara
        // primero por claridad, como el resto de rutas fijas de este grupo.
        Route::prefix('trash')->name('trash.')->group(function () {
            Route::get('/', [EmailLogController::class, 'trash'])->name('index');

            Route::middleware('throttle:6,1')
                ->post('/bulk-restore', [EmailLogController::class, 'bulkRestore'])
                ->name('bulk-restore');

            // ->withTrashed(): sin esto, el binding implícito de {emailLog}
            // (scope global SoftDeletes) nunca encontraría un registro que
            // ya está en la papelera y devolvería 404 en vez de operar sobre
            // él (ver Illuminate\Routing\Route::withTrashed()).
            Route::post('/{emailLog}/restore', [EmailLogController::class, 'restore'])
                ->name('restore')
                ->whereUuid('emailLog')
                ->withTrashed();

            Route::delete('/{emailLog}', [EmailLogController::class, 'forceDestroy'])
                ->name('force-destroy')
                ->whereUuid('emailLog')
                ->withTrashed();
        });

        Route::get('/{emailLog}', [EmailLogController::class, 'show'])->name('show')->whereUuid('emailLog');

        Route::get('/{emailLog}/download', [EmailLogController::class, 'download'])
            ->name('download')
            ->whereUuid('emailLog');

        Route::get('/{emailLog}/raw', [EmailLogController::class, 'downloadRaw'])
            ->name('raw')
            ->whereUuid('emailLog');

        Route::middleware('throttle:12,1')
            ->post('/{emailLog}/resend', [EmailLogController::class, 'resend'])
            ->name('resend')
            ->whereUuid('emailLog');

        Route::middleware('throttle:6,1')
            ->post('/bulk-resend', [EmailLogController::class, 'bulkResend'])
            ->name('bulk-resend');

        Route::post('/{emailLog}/purge-body', [EmailLogController::class, 'purgeBody'])
            ->name('purge-body')
            ->whereUuid('emailLog');

        Route::delete('/bulk', [EmailLogController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/{emailLog}', [EmailLogController::class, 'destroy'])->name('destroy')->whereUuid('emailLog');
    });

Route::middleware('auth')
    ->prefix('panel/settings/helpdeskemaillog')
    ->name('settings.helpdeskemaillog.')
    ->group(function () {
        Route::get('/', [EmailLogSettingsController::class, 'index'])->name('index');
        Route::patch('/', [EmailLogSettingsController::class, 'update'])->name('update');

        Route::prefix('bounce-mailboxes')->name('bounce-mailboxes.')->group(function () {
            Route::get('/', [BounceMailboxesController::class, 'index'])->name('index');
            Route::post('/', [BounceMailboxesController::class, 'store'])->name('store');
            Route::put('/{mailbox}', [BounceMailboxesController::class, 'update'])->name('update');
            Route::delete('/{mailbox}', [BounceMailboxesController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('suppressions')->name('suppressions.')->group(function () {
            Route::get('/', [EmailSuppressionController::class, 'index'])->name('index');
            Route::post('/', [EmailSuppressionController::class, 'store'])->name('store');
            Route::delete('/{suppression}', [EmailSuppressionController::class, 'destroy'])->name('destroy');
        });
    });
