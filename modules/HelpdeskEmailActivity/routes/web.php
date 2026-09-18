<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailClickTrackingController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailDeliveryLookupController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailGdprController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailInspectionController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailLogAnalyticsController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailLogController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailLogViewsController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailOpenTrackingController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailProviderWebhookController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailQueueController;
use Modules\HelpdeskEmailActivity\Http\Controllers\EmailReputationController;
use Modules\HelpdeskEmailActivity\Http\Controllers\Settings\BounceMailboxesController;
use Modules\HelpdeskEmailActivity\Http\Controllers\Settings\EmailLogSettingsController;
use Modules\HelpdeskEmailActivity\Http\Controllers\Settings\EmailSuppressionController;
use Modules\HelpdeskEmailActivity\Http\Controllers\Settings\WebhookEventsController;

// Pixel de apertura — SIN auth a propósito: lo carga el cliente de correo del
// destinatario, no un usuario logueado del panel. Fuera del prefix
// panel/helpdeskemailactivity (que exige auth) y del throttle agresivo del resto
// del módulo (un cliente de correo puede reintentar la carga varias veces).
// throttle:120,1 por IP: generoso para reintentos legítimos de un cliente de
// correo, suficiente para frenar la enumeración/DoS de UIDs desde una sola
// fuente (el UUID en sí ya es el control principal, esto es defensa en
// profundidad — ver auditoría de seguridad de la Fase 0).
Route::middleware('throttle:120,1')
    ->get('/e/{emailLog:uid}.gif', [EmailOpenTrackingController::class, 'pixel'])
    ->whereUuid('emailLog')
    ->name('helpdeskemailactivity.pixel');

// Redirección de clic — misma lógica de exposición pública que el píxel de
// arriba: la sigue el navegador/cliente de correo del destinatario al pulsar
// un enlace reescrito (ver LogEmailQueued::injectClickTracking), nunca un
// usuario logueado del panel.
Route::middleware('throttle:120,1')
    ->get('/e/{emailLog:uid}/c/{token}', [EmailClickTrackingController::class, 'redirect'])
    ->whereUuid('emailLog')
    ->where('token', '[A-Za-z0-9]+')
    ->name('helpdeskemailactivity.click');

// Webhooks de proveedor — SIN auth (los llama el proveedor externo, no un
// usuario logueado); la autenticidad la valida cada adapter (firma/token
// propio del proveedor), no una sesión. Bajo /webhooks/ a propósito: es el
// prefijo que Modules\Core\Http\Middleware\VerifyCsrfToken ya excluye
// globalmente (mismo patrón que Mailer/HelpdeskSocial) — un POST externo
// nunca trae token CSRF. Throttle generoso: un proveedor real puede reenviar
// ráfagas de eventos.
Route::middleware('throttle:120,1')
    ->post('/webhooks/helpdeskemailactivity/{provider}', [EmailProviderWebhookController::class, 'receive'])
    ->name('helpdeskemailactivity.webhooks.receive');

// The `web` middleware group is applied by the service provider.
Route::middleware('auth')
    ->prefix('panel/helpdeskemailactivity')
    ->name('helpdeskemailactivity.')
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

        // Buscador de tickets para el modal "Vincular a un ticket" del sidebar
        // (ver EmailLogController::searchTickets()/linkEntity()) — literal
        // 'tickets/search' antes del wildcard {emailLog} de abajo, mismo
        // criterio que 'export'/'reputation'/'trash'.
        Route::get('/tickets/search', [EmailLogController::class, 'searchTickets'])->name('tickets.search');

        Route::prefix('reputation')->name('reputation.')->group(function () {
            Route::get('/', [EmailReputationController::class, 'index'])->name('index');
            Route::post('/refresh', [EmailReputationController::class, 'refresh'])->name('refresh');
        });

        // Analítica (rendimiento por mailable, latencia de entrega,
        // entregabilidad por dominio destinatario). Solo lectura: sin throttle
        // propio, mismo criterio que 'reputation'/'trash'. Va antes del
        // wildcard {emailLog} de abajo por el mismo motivo que las anteriores.
        Route::get('/analytics', [EmailLogAnalyticsController::class, 'index'])->name('analytics.index');

        // API de consulta de entregabilidad para otros módulos: "¿se le envió
        // a este cliente?, ¿lo abrió?". Devuelve JSON, no vistas. Literal
        // antes del wildcard {emailLog}, igual que las anteriores.
        //
        // La consumen HelpdeskBirthday (panel de campañas), y está pensada
        // para que Document, HelpdeskTickets y cualquier otro módulo pinten el
        // estado de sus envíos sin replicar consultas sobre email_logs.
        Route::prefix('lookup')->name('lookup.')->group(function () {
            Route::get('/recipient', [EmailDeliveryLookupController::class, 'recipient'])->name('recipient');
            Route::post('/recipients', [EmailDeliveryLookupController::class, 'recipients'])->name('recipients');
            Route::get('/entity', [EmailDeliveryLookupController::class, 'entity'])->name('entity');
            Route::get('/modules', [EmailDeliveryLookupController::class, 'modules'])->name('modules');
            // {emailModule} y NO {module}: nwidart/laravel-modules intercepta
            // globalmente cualquier parámetro de ruta llamado 'module' y
            // responde 404 ("The X module is currently disabled") cuando su
            // valor no es un módulo instalado y activo. Como aquí el valor es
            // la columna `module` de email_logs —que puede no coincidir con
            // ningún módulo, p. ej. en pruebas o en correos de terceros—, ese
            // nombre de parámetro rompía el endpoint en silencio.
            Route::get('/module/{emailModule}/stats', [EmailDeliveryLookupController::class, 'moduleStats'])->name('module-stats');
        });

        // Jobs fallidos de la cola de correo — todas las acciones acotadas a
        // la cola 'emails' dentro del propio controlador (failed_jobs es una
        // tabla compartida por toda la app). Literal antes del wildcard
        // {emailLog}, igual que 'views'/'trash'.
        Route::prefix('queue')->name('queue.')->group(function () {
            Route::get('/', [EmailQueueController::class, 'index'])->name('index');
            Route::post('/retry-all', [EmailQueueController::class, 'retryAll'])->name('retry-all');
            Route::delete('/flush', [EmailQueueController::class, 'flush'])->name('flush');
            Route::post('/{uuid}/retry', [EmailQueueController::class, 'retry'])->name('retry');
        });

        // Derecho de supresión del RGPD — acción irreversible, ver
        // EmailGdprController y RecipientAnonymizerService.
        Route::prefix('gdpr')->name('gdpr.')->group(function () {
            Route::post('/preview', [EmailGdprController::class, 'preview'])->name('preview');
            Route::post('/anonymize', [EmailGdprController::class, 'anonymize'])->name('anonymize');
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

        // withTrashed(): un registro en la papelera sigue siendo inspeccionable
        // —es justo cuando interesa mirarlo antes de restaurarlo o purgarlo—,
        // y sin esto el binding implícito devolvería 404 por el scope global de
        // SoftDeletes. El panel entra en modo de solo lectura, ver
        // resolveDetailData()/detail-panel.blade.php.
        Route::get('/{emailLog}', [EmailLogController::class, 'show'])
            ->name('show')
            ->whereUuid('emailLog')
            ->withTrashed();

        Route::get('/{emailLog}/download', [EmailLogController::class, 'download'])
            ->name('download')
            ->whereUuid('emailLog');

        Route::get('/{emailLog}/raw', [EmailLogController::class, 'downloadRaw'])
            ->name('raw')
            ->whereUuid('emailLog');

        Route::get('/{emailLog}/download-text', [EmailLogController::class, 'downloadText'])
            ->name('download-text')
            ->whereUuid('emailLog');

        // Inspector de mensaje (pestañas "Compatibilidad" y "Enlaces"): ambas
        // devuelven JSON para el panel de detalle, no vistas.
        Route::get('/{emailLog}/html-check', [EmailInspectionController::class, 'htmlCheck'])
            ->name('html-check')
            ->whereUuid('emailLog');

        // POST y con throttle propio: cada llamada abre peticiones HTTP reales
        // a todos los enlaces del correo (hacia terceros), no es una lectura.
        Route::middleware('throttle:12,1')
            ->post('/{emailLog}/link-check', [EmailInspectionController::class, 'linkCheck'])
            ->name('link-check')
            ->whereUuid('emailLog');

        // La última comprobación guardada sí es una lectura: no sale a la red,
        // solo consulta email_link_checks.
        Route::get('/{emailLog}/link-check', [EmailInspectionController::class, 'linkCheckHistory'])
            ->name('link-check-history')
            ->whereUuid('emailLog');

        Route::middleware('throttle:12,1')
            ->post('/{emailLog}/resend', [EmailLogController::class, 'resend'])
            ->name('resend')
            ->whereUuid('emailLog');

        // Triaje de rebotes en un solo paso: corrige el destinatario, reenvía
        // y opcionalmente suprime la dirección vieja (ver
        // EmailLogController::resolveBounce()). Mismo throttle que resend():
        // ES un reenvío, solo que orquestado junto a la supresión.
        Route::middleware('throttle:12,1')
            ->post('/{emailLog}/resolve-bounce', [EmailLogController::class, 'resolveBounce'])
            ->name('resolve-bounce')
            ->whereUuid('emailLog');

        Route::post('/{emailLog}/link-entity', [EmailLogController::class, 'linkEntity'])
            ->name('link-entity')
            ->whereUuid('emailLog');

        Route::middleware('throttle:6,1')
            ->post('/bulk-resend', [EmailLogController::class, 'bulkResend'])
            ->name('bulk-resend');

        // Reenvío de TODO lo que casa con el filtro activo. Throttle más
        // estricto que el de selección manual (2/min en vez de 6): cada
        // llamada puede encolar cientos de correos reales.
        Route::middleware('throttle:2,1')
            ->post('/bulk-resend-filtered', [EmailLogController::class, 'bulkResendFiltered'])
            ->name('bulk-resend-filtered');

        Route::post('/{emailLog}/purge-body', [EmailLogController::class, 'purgeBody'])
            ->name('purge-body')
            ->whereUuid('emailLog');

        Route::delete('/bulk', [EmailLogController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/{emailLog}', [EmailLogController::class, 'destroy'])->name('destroy')->whereUuid('emailLog');
    });

Route::middleware('auth')
    ->prefix('panel/settings/helpdeskemailactivity')
    ->name('settings.helpdeskemailactivity.')
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

        // Eventos de webhook recibidos (payload, correlación con el email y
        // reproceso de los que no correlacionaron) + salud por proveedor.
        // Los permisos los aplica el propio controlador en su constructor
        // (settings.view / settings.update), como el resto de este grupo.
        Route::prefix('webhook-events')->name('webhook-events.')->group(function () {
            Route::get('/', [WebhookEventsController::class, 'index'])->name('index');
            Route::post('/{event}/reprocess', [WebhookEventsController::class, 'reprocess'])->name('reprocess');
        });
    });
