<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\ApplyAiSuggestionController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\BulkTicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\HelpdeskReportsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\MacroApplyController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\RecurringTicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\ScheduledRepliesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\AutomationsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\MacrosController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCannedRepliesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCategoriesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCategoryFieldsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketEmailBlacklistController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketEmailChannelsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketGeneralSettingsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketGroupsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketPrioritiesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketQuarantineController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketSlaPoliciesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketStatusesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketViewsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\SuggestedArticlesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketAiSuggestionController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketAttachmentDownloadController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketCommentsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketDetailDataController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketExportController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketFollowupsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketLifecycleController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMailDetailDataController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMailsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMessagingController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketNotesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsAutomationsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsMailboxesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsNotificationsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsRecurringController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsSlaController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketOpsWorkloadController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketPresenceController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketsCrudController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketSearchController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketSideConversationsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketTranslationController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketUnificationController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TimeEntriesController;

/*
 * Los 15 permisos `helpdesk.tickets.*` se sembraban y se podían asignar desde
 * el panel de roles, pero NINGUNA ruta los consultaba: quitarle
 * `helpdesk.tickets.delete` a un rol no le impedía borrar nada. Servían solo
 * para pintar el menú.
 *
 * Se cablean donde el daño es irreversible o el alcance excede el trabajo
 * diario —borrar tickets, borrar correos, configurar el módulo— y NO como
 * permiso único de entrada al grupo. Se probó lo segundo y bloquea un caso
 * legítimo: quien tiene `helpdesk.tickets.update` pero no `.view` (462 y 498
 * usuarios en esta base de datos, y no son los mismos) trabaja tickets sin
 * tener marcado el permiso de verlos. Un permiso de acción implica poder
 * entrar; exigir `.view` por encima les cerraría el módulo entero.
 *
 * Quién entra sigue decidiéndolo `role:super-admin|super-settings`, declarado
 * al montar el grupo en HelpdeskTicketsServiceProvider.
 */
Route::group(['prefix' => ''], function () {

    // Advanced search
    Route::get('search', [TicketSearchController::class, 'index'])->name('manager.helpdesk.search');

    // Buscador ligero para los modales "Fusionar" y "Vincular ticket", que
    // hasta ahora exigían teclear el ID numérico a mano.
    Route::get('/tickets/search', [TicketOpsController::class, 'search'])->name('manager.helpdesk.tickets.search');

    // Macros (apply to ticket)
    Route::get('/macros/available', [MacroApplyController::class, 'list'])->name('manager.helpdesk.macros.list');
    Route::post('/tickets/{ticket}/macros/{macro}/apply', [MacroApplyController::class, 'apply'])->name('manager.helpdesk.tickets.macros.apply');

    // Artículos del centro de ayuda sugeridos al responder (deflexión)
    Route::get('/tickets/{ticket}/suggested-articles', [SuggestedArticlesController::class, 'index'])->name('manager.helpdesk.tickets.suggested-articles');

    // Plantillas de email (TicketCannedReply) con las variables {{...}} ya
    // resueltas contra ESTE ticket. index() manda la lista en bruto una sola
    // vez para toda la sesión SPA (no sabe con qué ticket se va a responder
    // todavía); el modal "Plantillas de email" pide esto al abrirse para
    // insertar el texto ya interpolado en vez del placeholder sin resolver
    // (bug reportado 11-sep-2026: {{cliente.nombre}} llegaba tal cual).
    Route::get('/tickets/{ticket}/canned-replies', [TicketsCrudController::class, 'cannedReplies'])->name('manager.helpdesk.tickets.canned-replies');

    // "Duplicar como mía" (TicketCannedReply) — a propósito FUERA del grupo
    // 'settings/tickets' (más abajo, gateado por can:helpdesk.tickets.settings):
    // cualquier agente sin ese permiso puede querer su propia copia editable
    // de una plantilla global desde el modal "Plantillas de email" del propio
    // ticket, no solo quien administra plantillas. Mismo controlador que la
    // pantalla de ajustes (TicketCannedRepliesController::duplicate()), que
    // excluye esta acción de su middleware de permiso en el constructor.
    Route::post('/tickets/canned-replies/{reply}/duplicate', [TicketCannedRepliesController::class, 'duplicate'])->name('manager.helpdesk.tickets.canned-replies.duplicate');

    // Traducción de texto del ticket (mensaje entrante / borrador de respuesta)
    Route::post('/tickets/{ticket}/translate', [TicketTranslationController::class, 'translate'])->name('manager.helpdesk.tickets.translate');

    // Aplicar sugerencia de IA (categoría / prioridad)
    Route::post('/tickets/{ticket}/apply-ai-suggestion', [ApplyAiSuggestionController::class, 'apply'])->name('manager.helpdesk.tickets.apply-ai-suggestion');

    // Cuarentena de correo: revisión de lo retenido por el clasificador de
    // spam. Sin esta pantalla, "retener" y "descartar" serían lo mismo.
    Route::get('/settings/quarantine', [TicketQuarantineController::class, 'index'])->name('manager.helpdesk.settings.quarantine.index');
    Route::post('/settings/quarantine/{quarantine}/release', [TicketQuarantineController::class, 'release'])->name('manager.helpdesk.settings.quarantine.release');
    Route::post('/settings/quarantine/{quarantine}/confirm', [TicketQuarantineController::class, 'confirm'])->name('manager.helpdesk.settings.quarantine.confirm');

    // Borrador de respuesta generado por IA. Throttle propio y bajo: cada
    // llamada dispara un bucle de tool-calling contra el proveedor (varias
    // peticiones facturadas), no es una consulta barata como el resto.
    Route::post('/tickets/{ticket}/ai/suggest-reply', [TicketAiSuggestionController::class, 'suggestReply'])
        ->middleware('throttle:20,1')
        ->name('manager.helpdesk.tickets.ai.suggest-reply');

    // Revisión del borrador antes de enviar. Throttle más alto que la
    // sugerencia: es una sola llamada y se dispara en cada envío, no a
    // petición del agente.
    Route::post('/tickets/{ticket}/ai/check-reply', [TicketAiSuggestionController::class, 'checkReply'])
        ->middleware('throttle:60,1')
        ->name('manager.helpdesk.tickets.ai.check-reply');

    // Posibles duplicados. GET porque solo lee y el resultado es cacheable
    // por el navegador mientras el agente navega por la ficha.
    // Modal 35: comprobar duplicados con lo escrito en el formulario, antes
    // de que exista el ticket. Va ANTES de la ruta con {ticket} para que
    // 'duplicates-preview' no se interprete como un id.
    Route::post('/tickets/duplicates-preview', [TicketAiSuggestionController::class, 'duplicatesPreview'])
        ->name('manager.helpdesk.tickets.duplicates-preview');

    Route::get('/tickets/{ticket}/ai/duplicates', [TicketAiSuggestionController::class, 'duplicates'])
        ->name('manager.helpdesk.tickets.ai.duplicates');

    // Discutir la revisión de calidad del ticket. Las disputadas se excluyen
    // de las medias, así que esto tiene efecto real.
    Route::post('/tickets/{ticket}/ai/dispute-review', [TicketAiSuggestionController::class, 'disputeReview'])
        ->name('manager.helpdesk.tickets.ai.dispute-review');

    // Recordatorios de seguimiento del ticket
    Route::post('/tickets/{ticket}/followups', [TicketFollowupsController::class, 'store'])->name('manager.helpdesk.tickets.followups.store');
    Route::delete('/tickets/{ticket}/followups', [TicketFollowupsController::class, 'destroyAll'])->name('manager.helpdesk.tickets.followups.destroy-all');
    Route::delete('/tickets/{ticket}/followups/{followup}', [TicketFollowupsController::class, 'destroy'])->name('manager.helpdesk.tickets.followups.destroy');

    // Side conversations del ticket (hilos laterales privados)
    Route::get('/tickets/{ticket}/side-conversations', [TicketSideConversationsController::class, 'index'])->name('manager.helpdesk.tickets.side-conversations.index');
    Route::post('/tickets/{ticket}/side-conversations', [TicketSideConversationsController::class, 'store'])->name('manager.helpdesk.tickets.side-conversations.store');
    Route::post('/tickets/{ticket}/side-conversations/{sideConversation}/messages', [TicketSideConversationsController::class, 'addMessage'])->name('manager.helpdesk.tickets.side-conversations.messages.store');
    Route::post('/tickets/{ticket}/side-conversations/{sideConversation}/close', [TicketSideConversationsController::class, 'close'])->name('manager.helpdesk.tickets.side-conversations.close');

    // Presencia de agentes en el ticket (agent collision)
    Route::post('/tickets/{ticket}/presence', [TicketPresenceController::class, 'heartbeat'])->name('manager.helpdesk.tickets.presence.heartbeat');
    Route::delete('/tickets/{ticket}/presence', [TicketPresenceController::class, 'leave'])->name('manager.helpdesk.tickets.presence.leave');
    // Modal 23 "Bandeja compartida": avisar a un agente presente en el ticket.
    Route::post('/tickets/{ticket}/presence/nudge', [TicketPresenceController::class, 'nudge'])->name('manager.helpdesk.tickets.presence.nudge');

    // Respuestas programadas del ticket (send later)
    Route::get('/tickets/{ticket}/scheduled-replies', [ScheduledRepliesController::class, 'index'])->name('manager.helpdesk.tickets.scheduled-replies.index');
    Route::post('/tickets/{ticket}/scheduled-replies', [ScheduledRepliesController::class, 'store'])->name('manager.helpdesk.tickets.scheduled-replies.store');
    Route::delete('/tickets/{ticket}/scheduled-replies/{scheduledReply}', [ScheduledRepliesController::class, 'destroy'])->name('manager.helpdesk.tickets.scheduled-replies.destroy');

    // Tickets bulk action
    Route::post('/tickets/bulk', [BulkTicketsController::class, 'handle'])->name('manager.helpdesk.tickets.bulk');

    // Tickets export
    Route::get('/tickets/export/{format}', [TicketExportController::class, 'export'])->name('manager.helpdesk.tickets.export');
    // Cuántas filas saldrían con el alcance elegido, para que el modal lo
    // diga antes de descargar.
    Route::get('/tickets/export-estimate', [TicketExportController::class, 'estimate'])->name('manager.helpdesk.tickets.export-estimate');

    // Modales del riel de operación. Van bajo /tickets/ops/ a propósito: con
    // un solo segmento los captura Route::get('/tickets/{ticket}') de más
    // abajo, que intentaría resolver el literal como un ticket y devolvería
    // 404 (comprobado).
    Route::get('/tickets/ops/notification-preferences', [TicketOpsNotificationsController::class, 'index'])->name('manager.helpdesk.tickets.notification-preferences');
    Route::post('/tickets/ops/notification-preferences', [TicketOpsNotificationsController::class, 'update'])->name('manager.helpdesk.tickets.notification-preferences.update');
    // Modal 31: webhooks de Slack/Teams (canal de equipo, no preferencia personal).
    Route::patch('/tickets/ops/notification-preferences/team-channels', [TicketOpsNotificationsController::class, 'updateTeamChannels'])->name('manager.helpdesk.tickets.notification-preferences.team-channels');
    Route::post('/tickets/ops/notification-preferences/team-channels/test', [TicketOpsNotificationsController::class, 'testTeamChannels'])->name('manager.helpdesk.tickets.notification-preferences.team-channels.test');

    // Modal "Tickets recurrentes": alta, edición y pausa sin salir del listado.
    Route::get('/tickets/ops/recurring', [TicketOpsRecurringController::class, 'index'])->name('manager.helpdesk.tickets.recurring.index');
    Route::post('/tickets/ops/recurring', [TicketOpsRecurringController::class, 'store'])->name('manager.helpdesk.tickets.recurring.store');
    // POST y no PUT: un PUT real por AJAX devuelve 405 en este Docker aunque
    // route:list lo muestre (gotcha ya documentado en el proyecto).
    Route::post('/tickets/ops/recurring/{recurringTicket}', [TicketOpsRecurringController::class, 'update'])->name('manager.helpdesk.tickets.recurring.update');
    Route::post('/tickets/ops/recurring/{recurringTicket}/toggle', [TicketOpsRecurringController::class, 'toggle'])->name('manager.helpdesk.tickets.recurring.toggle');

    // Modal "Buzones de entrada": estado, los dos interruptores de
    // comportamiento y una prueba de conexión del buzón YA guardado (el
    // endpoint de ajustes prueba un host del request; éste solo el propio).
    Route::get('/tickets/ops/mailboxes', [TicketOpsMailboxesController::class, 'index'])->name('manager.helpdesk.tickets.mailboxes.index');
    Route::post('/tickets/ops/mailboxes/{channel}/behavior', [TicketOpsMailboxesController::class, 'behavior'])->name('manager.helpdesk.tickets.mailboxes.behavior');
    Route::post('/tickets/ops/mailboxes/{channel}/test', [TicketOpsMailboxesController::class, 'test'])->name('manager.helpdesk.tickets.mailboxes.test');

    // Modal "Carga de agentes": resumen por agente y por equipo + ajustes de
    // reparto. Sustituye a TicketOpsController::workload(), que hacía un
    // COUNT por agente dentro del map.
    Route::get('/tickets/workload/overview', [TicketOpsWorkloadController::class, 'overview'])->name('manager.helpdesk.tickets.workload.overview');
    Route::post('/tickets/workload/assignment', [TicketOpsWorkloadController::class, 'updateAssignment'])->name('manager.helpdesk.tickets.workload.assignment');

    // Modal "Horario y SLA": reloj del ticket, objetivos por política y los
    // estados que pausan el contador.
    Route::get('/tickets/ops/sla-calendar', [TicketOpsSlaController::class, 'calendar'])->name('manager.helpdesk.tickets.sla-calendar');
    Route::post('/tickets/ops/sla-calendar/pause-status', [TicketOpsSlaController::class, 'updatePauseStatus'])->name('manager.helpdesk.tickets.sla-calendar.pause-status');

    // Modal "Regla de escalado": editor Si… Entonces… acotado a lo que
    // AutomationEngine sabe ejecutar de verdad.
    Route::get('/tickets/ops/automations', [TicketOpsAutomationsController::class, 'index'])->name('manager.helpdesk.tickets.automations.index');
    Route::post('/tickets/ops/automations', [TicketOpsAutomationsController::class, 'store'])->name('manager.helpdesk.tickets.automations.store');
    Route::post('/tickets/ops/automations/preview', [TicketOpsAutomationsController::class, 'preview'])->name('manager.helpdesk.tickets.automations.preview');
    Route::post('/tickets/ops/automations/{automation}/toggle', [TicketOpsAutomationsController::class, 'toggle'])->name('manager.helpdesk.tickets.automations.toggle');

    // Emails de tickets — la bandeja GLOBAL propia (listado/browsing
    // cross-ticket) se retiró: vive ahora en
    // /panel/helpdeskemailactivity?module=HelpdeskTickets (auditoría cross-módulo
    // unificada, ver plan de unificación). El nombre de ruta 'emails.index'
    // se conserva como redirect (TicketMailsController::index(), rama no-JSON)
    // porque enlaces/menú/marcadores existentes lo siguen usando. Las demás
    // rutas de aquí NO son la bandeja retirada — son la API que consume el
    // composer del listado (tickets-app.js; vivió un tiempo dentro de la
    // ficha del ticket, TicketsCrudController::showFull, retirada el
    // 8-sep-2026) y el modal de "Entregabilidad de correo"/refetch de
    // stats de tickets-app.js (ambos con Accept: json).
    Route::get('/tickets/emails', [TicketMailsController::class, 'index'])->name('manager.helpdesk.tickets.emails.index');
    Route::get('/tickets/scheduled', [TicketMailsController::class, 'scheduled'])->name('manager.helpdesk.tickets.scheduled');
    Route::get('/tickets/emails/export', [TicketMailsController::class, 'export'])->name('manager.helpdesk.tickets.emails.export');
    Route::get('/tickets/emails/templates', [TicketMailsController::class, 'templates'])->name('manager.helpdesk.tickets.emails.templates');
    Route::post('/tickets/emails', [TicketMailsController::class, 'store'])->name('manager.helpdesk.tickets.emails.store');
    Route::post('/tickets/emails/bulk', [TicketMailsController::class, 'bulk'])->name('manager.helpdesk.tickets.emails.bulk');
    Route::get('/tickets/emails/{mail}', [TicketMailDetailDataController::class, 'data'])->name('manager.helpdesk.tickets.emails.data');
    Route::post('/tickets/emails/{mail}/resend', [TicketMailsController::class, 'resend'])->name('manager.helpdesk.tickets.emails.resend');
    // Modales 16/28/29/30: lectura agrupada de la configuración que consultan.
    Route::get('/tickets/settings-snapshot', [TicketOpsController::class, 'settingsSnapshot'])->name('manager.helpdesk.tickets.settings-snapshot');
    // Modal 39: separa mensajes del hilo en un ticket nuevo.
    Route::post('/tickets/{ticket}/split', [TicketOpsController::class, 'split'])->name('manager.helpdesk.tickets.split');
    // Modal 22: reputación y autenticación del dominio de envío.
    Route::get('/tickets/reputation', [TicketOpsController::class, 'reputation'])->name('manager.helpdesk.tickets.reputation');
    // Modal 22: guarda "avisar a managers"/"suprimir automáticamente".
    Route::patch('/tickets/reputation', [TicketOpsController::class, 'updateReputation'])->name('manager.helpdesk.tickets.reputation.update');
    // Modal 25 "Cliente 360": pedidos PrestaShop del cliente, bajo demanda.
    Route::get('/tickets/{ticket}/customer-360/orders', [TicketOpsController::class, 'customerOrders'])->name('manager.helpdesk.tickets.customer-360.orders');
    // Modal 27: "aplicar automáticamente si la confianza supera el 90%".
    Route::patch('/tickets/ai-auto-apply', [TicketOpsController::class, 'updateAiAutoApply'])->name('manager.helpdesk.tickets.ai-auto-apply.update');
    // Modal 09: cancela un envío programado (a borrador o eliminándolo).
    Route::post('/tickets/emails/{mail}/cancel-scheduled', [TicketMailsController::class, 'cancelScheduled'])->name('manager.helpdesk.tickets.emails.cancel-scheduled');
    // Modal 10: mueve un correo (y opcionalmente su hilo) a otro ticket.
    Route::post('/tickets/emails/{mail}/link', [TicketMailsController::class, 'linkToTicket'])->name('manager.helpdesk.tickets.emails.link');
    // Modal 06 "Email rebotado": corrige destinatario, suprime la dirección
    // vieja y reenvía, todo en una transacción (ver fixBounce()).
    Route::post('/tickets/emails/{mail}/fix-bounce', [TicketMailsController::class, 'fixBounce'])->name('manager.helpdesk.tickets.emails.fix-bounce');
    Route::patch('/tickets/emails/{mail}/tags', [TicketMailsController::class, 'updateTags'])->name('manager.helpdesk.tickets.emails.tags');
    Route::post('/tickets/emails/{mail}/translate', [TicketMailsController::class, 'translate'])->name('manager.helpdesk.tickets.emails.translate');
    Route::get('/tickets/emails/{mail}/summary', [TicketMailsController::class, 'summary'])->name('manager.helpdesk.tickets.emails.summary');
    Route::delete('/tickets/emails/{mail}', [TicketMailsController::class, 'destroy'])->name('manager.helpdesk.tickets.emails.destroy')->middleware('can:helpdesk.tickets.emails.delete');

    // Tickets CRUD (listado de vuelta en /tickets — ver comentario arriba)
    Route::get('/tickets', [TicketsCrudController::class, 'index'])->name('manager.helpdesk.tickets.index');
    Route::get('/tickets/create', [TicketsCrudController::class, 'create'])->name('manager.helpdesk.tickets.create');
    Route::post('/tickets', [TicketsCrudController::class, 'store'])->name('manager.helpdesk.tickets.store');
    // Guardado rápido de vista personal desde el listado — a diferencia de
    // Settings/TicketViewsController (gestión admin, permiso
    // helpdesk.tickets.settings, sin scoping por usuario), este endpoint es
    // para cualquier agente con permiso de ver el listado, scopeado a sus
    // propias vistas (Fase D).
    Route::post('/tickets/views', [TicketsCrudController::class, 'storeView'])->name('manager.helpdesk.tickets.views.store');
    // Pills "Cola"/"Carga" del listado — antes de {ticket} para que no las
    // capture el catch-all (mismo gotcha ya documentado en este archivo).
    Route::get('/tickets/ops', [TicketOpsController::class, 'ops'])->name('manager.helpdesk.tickets.ops');
    Route::get('/tickets/workload', [TicketOpsController::class, 'workload'])->name('manager.helpdesk.tickets.workload');
    Route::post('/tickets/workload/distribute', [TicketOpsController::class, 'distributeUnassigned'])->name('manager.helpdesk.tickets.workload.distribute');
    // Modal 17 "Cola y reintentos". Reencolar y purgar tocan jobs reales
    // (correos a clientes), así que el controlador exige además el permiso
    // de ajustes del módulo.
    Route::post('/tickets/queue/retry', [TicketOpsController::class, 'retryFailedJobs'])->name('manager.helpdesk.tickets.queue.retry');
    Route::post('/tickets/queue/flush', [TicketOpsController::class, 'flushFailedJobs'])->name('manager.helpdesk.tickets.queue.flush');
    Route::get('/tickets/{ticket}', [TicketsCrudController::class, 'show'])->name('manager.helpdesk.tickets.show');
    // JSON de detalle para el panel de "Gestión de tickets" (Fase B): hilo,
    // actividad, archivos y correo — mismo patrón que
    // manager.helpdesk.tickets.emails.data.
    Route::get('/tickets/{ticket}/data', [TicketDetailDataController::class, 'data'])->name('manager.helpdesk.tickets.data');
    // Sonda ligera para el refresco automático del panel: dice si hay
    // algo nuevo sin armar el hilo entero. Ver TicketDetailDataController::pulse().
    Route::get('/tickets/{ticket}/pulse', [TicketDetailDataController::class, 'pulse'])->name('manager.helpdesk.tickets.pulse');
    Route::get('/tickets/{ticket}/summary', [TicketOpsController::class, 'summary'])->name('manager.helpdesk.tickets.summary');
    Route::patch('/tickets/{ticket}/tags', [TicketsCrudController::class, 'tags'])->name('manager.helpdesk.tickets.tags');
    Route::get('/tickets/{ticket}/edit', [TicketsCrudController::class, 'edit'])->name('manager.helpdesk.tickets.edit');
    Route::put('/tickets/{ticket}', [TicketsCrudController::class, 'update'])->name('manager.helpdesk.tickets.update');
    Route::delete('/tickets/{ticket}', [TicketsCrudController::class, 'destroy'])->name('manager.helpdesk.tickets.destroy')->middleware('can:helpdesk.tickets.delete');
    Route::post('/tickets/{ticket}/restore', [TicketsCrudController::class, 'restore'])->name('manager.helpdesk.tickets.restore')->withTrashed();
    Route::delete('/tickets/{ticket}/force-delete', [TicketsCrudController::class, 'forceDelete'])->name('manager.helpdesk.tickets.force-delete')->withTrashed()->middleware('can:helpdesk.tickets.delete');

    // Ticket lifecycle
    Route::post('/tickets/{ticket}/close', [TicketLifecycleController::class, 'close'])->name('manager.helpdesk.tickets.close');
    Route::post('/tickets/{ticket}/resolve', [TicketLifecycleController::class, 'resolve'])->name('manager.helpdesk.tickets.resolve');
    Route::post('/tickets/{ticket}/reopen', [TicketLifecycleController::class, 'reopen'])->name('manager.helpdesk.tickets.reopen');
    Route::post('/tickets/{ticket}/archive', [TicketLifecycleController::class, 'archive'])->name('manager.helpdesk.tickets.archive');
    Route::post('/tickets/{ticket}/csat/send', [TicketOpsController::class, 'sendCsatSurvey'])->name('manager.helpdesk.tickets.csat.send');
    // Modal 32 "Portal del cliente": enviar el enlace mágico de acceso.
    Route::post('/tickets/{ticket}/portal/send-access', [TicketOpsController::class, 'sendPortalAccess'])->name('manager.helpdesk.tickets.portal.send-access');
    // Modal 40: la valoración recibida y el contexto para leerla.
    Route::get('/tickets/{ticket}/csat', [TicketOpsController::class, 'csat'])->name('manager.helpdesk.tickets.csat.show');
    Route::post('/tickets/{ticket}/unarchive', [TicketLifecycleController::class, 'unarchive'])->name('manager.helpdesk.tickets.unarchive');
    Route::post('/tickets/{ticket}/merge', [TicketLifecycleController::class, 'merge'])->name('manager.helpdesk.tickets.merge');

    /*
     * Unificar duplicados (v2 del aviso de duplicados). La v1 —fusionar de uno
     * en uno desde el banner— sigue viva en la ruta de arriba; esto cubre el
     * caso de varios correos del mismo cliente el mismo día: resumen de cada
     * ticket, se conserva uno y el resto se cierran avisando al cliente.
     */
    Route::get('/tickets/{ticket}/unify/summary', [TicketUnificationController::class, 'summary'])->name('manager.helpdesk.tickets.unify.summary');
    Route::post('/tickets/{ticket}/unify', [TicketUnificationController::class, 'unify'])->name('manager.helpdesk.tickets.unify');

    // Bloquear al remitente (correo y/o dominio) y borrar el ticket de una vez.
    Route::post('/tickets/{ticket}/blacklist', [TicketUnificationController::class, 'blacklist'])->name('manager.helpdesk.tickets.blacklist');
    Route::post('/tickets/{ticket}/watch', [TicketLifecycleController::class, 'watch'])->name('manager.helpdesk.tickets.watch');
    Route::delete('/tickets/{ticket}/watch', [TicketLifecycleController::class, 'unwatch'])->name('manager.helpdesk.tickets.unwatch');
    Route::post('/tickets/{ticket}/snooze', [TicketLifecycleController::class, 'snooze'])->name('manager.helpdesk.tickets.snooze');
    Route::delete('/tickets/{ticket}/snooze', [TicketLifecycleController::class, 'unsnooze'])->name('manager.helpdesk.tickets.unsnooze');
    Route::post('/tickets/{ticket}/link', [TicketLifecycleController::class, 'linkTicket'])->name('manager.helpdesk.tickets.link');
    Route::delete('/tickets/{ticket}/link/{linkId}', [TicketLifecycleController::class, 'unlinkTicket'])->name('manager.helpdesk.tickets.unlink');

    // Ticket messaging
    Route::post('/tickets/bulk-reply', [TicketMessagingController::class, 'bulkReply'])->name('manager.helpdesk.tickets.bulk-reply');
    Route::post('/tickets/{ticket}/messages', [TicketMessagingController::class, 'storeMessage'])->name('manager.helpdesk.tickets.messages.store');
    Route::post('/tickets/{ticket}/typing', [TicketMessagingController::class, 'typing'])->name('manager.helpdesk.tickets.typing');

    // Attachment download (private disk — authorised agents only)
    Route::get('/tickets/{ticket}/attachments/{item}/{index}', [TicketAttachmentDownloadController::class, 'download'])
        ->name('manager.helpdesk.tickets.attachments.download')
        ->whereNumber('index');

    // Adjuntos subidos por el cliente (portal / widget / formulario público).
    // Viven en TicketAttachment, no en TicketItem.attachment_urls, y hasta
    // ahora no tenían ninguna ruta que los sirviera.
    Route::get('/tickets/{ticket}/message-attachments/{attachment}', [TicketAttachmentDownloadController::class, 'downloadMessageAttachment'])
        ->name('manager.helpdesk.tickets.message-attachments.download')
        ->whereNumber('attachment');

    // Ticket time entries
    Route::get('/tickets/{ticket}/time-entries', [TimeEntriesController::class, 'index'])->name('manager.helpdesk.tickets.time-entries.index');
    Route::post('/tickets/{ticket}/time-entries', [TimeEntriesController::class, 'store'])->name('manager.helpdesk.tickets.time-entries.store');
    Route::delete('/tickets/{ticket}/time-entries/{timeEntry}', [TimeEntriesController::class, 'destroy'])->name('manager.helpdesk.tickets.time-entries.destroy');

    // Ticket comments
    Route::get('/tickets/{ticket}/comments', [TicketCommentsController::class, 'index'])->name('manager.helpdesk.tickets.comments.index');
    Route::post('/tickets/{ticket}/comments', [TicketCommentsController::class, 'store'])->name('manager.helpdesk.tickets.comments.store');
    Route::get('/tickets/{ticket}/comments/{comment}', [TicketCommentsController::class, 'show'])->name('manager.helpdesk.tickets.comments.show');
    Route::put('/tickets/{ticket}/comments/{comment}', [TicketCommentsController::class, 'update'])->name('manager.helpdesk.tickets.comments.update');
    Route::delete('/tickets/{ticket}/comments/{comment}', [TicketCommentsController::class, 'destroy'])->name('manager.helpdesk.tickets.comments.destroy');
    Route::post('/tickets/{ticket}/comments/{comment}/restore', [TicketCommentsController::class, 'restore'])->name('manager.helpdesk.tickets.comments.restore');

    // Ticket notes
    Route::post('/tickets/{ticket}/notes', [TicketNotesController::class, 'store'])->name('manager.helpdesk.tickets.notes.store');
    Route::get('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'show'])->name('manager.helpdesk.tickets.notes.show');
    Route::delete('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'destroy'])->name('manager.helpdesk.tickets.notes.destroy');
    Route::post('/tickets/{ticket}/notes/{note}/pin', [TicketNotesController::class, 'pin'])->name('manager.helpdesk.tickets.notes.pin');
    Route::post('/tickets/{ticket}/notes/{note}/color', [TicketNotesController::class, 'changeColor'])->name('manager.helpdesk.tickets.notes.color');

    // Ticket templates: movidas a routes/ticket-templates.php — necesitan un
    // gate de rol mas amplio (tambien agentes) que el resto de este archivo.

    // Recurring tickets
    Route::post('recurring-tickets/bulk-action', [RecurringTicketsController::class, 'bulkAction'])->name('manager.helpdesk.recurring-tickets.bulk-action');
    Route::resource('recurring-tickets', RecurringTicketsController::class)->names([
        'index' => 'manager.helpdesk.recurring-tickets.index',
        'create' => 'manager.helpdesk.recurring-tickets.create',
        'store' => 'manager.helpdesk.recurring-tickets.store',
        'edit' => 'manager.helpdesk.recurring-tickets.edit',
        'update' => 'manager.helpdesk.recurring-tickets.update',
        'destroy' => 'manager.helpdesk.recurring-tickets.destroy',
    ])->except(['show']);
    Route::post('recurring-tickets/{recurringTicket}/toggle', [RecurringTicketsController::class, 'toggle'])->name('manager.helpdesk.recurring-tickets.toggle');

    // General ticket configuration
    Route::get('settings/tickets/general', [TicketGeneralSettingsController::class, 'index'])->name('manager.helpdesk.settings.tickets.general')->middleware('can:helpdesk.tickets.settings');
    Route::put('settings/tickets/general', [TicketGeneralSettingsController::class, 'update'])->name('manager.helpdesk.settings.tickets.general.update')->middleware('can:helpdesk.tickets.settings');

    // Ticket settings
    //
    // Permiso propio: configurar categorías, estados, SLA, buzones o listas
    // negras no es lo mismo que trabajar tickets, y ninguno de estos
    // controladores autoriza por su cuenta — la única barrera era el rol de la
    // ruta. `helpdesk.tickets.settings` ya existía sembrado y sin usar.
    Route::prefix('settings/tickets')->middleware('can:helpdesk.tickets.settings')->name('manager.helpdesk.settings.')->group(function () {

        // Categories
        Route::prefix('categories')->name('ticket-categories.')->group(function () {
            Route::get('/', [TicketCategoriesController::class, 'index'])->name('index');
            Route::get('create', [TicketCategoriesController::class, 'create'])->name('create');
            Route::post('/', [TicketCategoriesController::class, 'store'])->name('store');
            Route::get('{category}/edit', [TicketCategoriesController::class, 'edit'])->name('edit');
            Route::put('{category}', [TicketCategoriesController::class, 'update'])->name('update');
            Route::patch('{category}/toggle', [TicketCategoriesController::class, 'toggle'])->name('toggle');
            Route::delete('{category}', [TicketCategoriesController::class, 'destroy'])->name('destroy');
            Route::post('reorder', [TicketCategoriesController::class, 'reorder'])->name('reorder');
            Route::post('bulk-action', [TicketCategoriesController::class, 'bulkAction'])->name('bulk-action');

            // Category fields CRUD (AJAX)
            Route::prefix('{category}/fields')->name('fields.')->group(function () {
                Route::get('/', [TicketCategoryFieldsController::class, 'index'])->name('index');
                Route::post('/', [TicketCategoryFieldsController::class, 'store'])->name('store');
                Route::patch('{field}', [TicketCategoryFieldsController::class, 'update'])->name('update');
                Route::delete('{field}', [TicketCategoryFieldsController::class, 'destroy'])->name('destroy');
                Route::post('reorder', [TicketCategoryFieldsController::class, 'reorder'])->name('reorder');
            });
        });

        // Groups
        Route::prefix('groups')->name('ticket-groups.')->group(function () {
            Route::get('/', [TicketGroupsController::class, 'index'])->name('index');
            Route::get('create', [TicketGroupsController::class, 'create'])->name('create');
            Route::post('/', [TicketGroupsController::class, 'store'])->name('store');
            Route::get('{group}/edit', [TicketGroupsController::class, 'edit'])->name('edit');
            Route::put('{group}', [TicketGroupsController::class, 'update'])->name('update');
            Route::patch('{group}/toggle', [TicketGroupsController::class, 'toggle'])->name('toggle');
            Route::delete('{group}', [TicketGroupsController::class, 'destroy'])->name('destroy');
            Route::post('reorder', [TicketGroupsController::class, 'reorder'])->name('reorder');
            Route::post('bulk-action', [TicketGroupsController::class, 'bulkAction'])->name('bulk-action');
        });

        // Canned replies
        Route::prefix('canned-replies')->name('ticket-canned-replies.')->group(function () {
            Route::get('/', [TicketCannedRepliesController::class, 'index'])->name('index');
            Route::get('create', [TicketCannedRepliesController::class, 'create'])->name('create');
            Route::post('/', [TicketCannedRepliesController::class, 'store'])->name('store');
            Route::get('{reply}/edit', [TicketCannedRepliesController::class, 'edit'])->name('edit');
            Route::put('{reply}', [TicketCannedRepliesController::class, 'update'])->name('update');
            Route::delete('{reply}', [TicketCannedRepliesController::class, 'destroy'])->name('destroy');
            Route::post('bulk-action', [TicketCannedRepliesController::class, 'bulkAction'])->name('bulk-action');
        });

        // Priorities
        Route::prefix('priorities')->name('ticket-priorities.')->group(function () {
            Route::get('/', [TicketPrioritiesController::class, 'index'])->name('index');
            Route::get('create', [TicketPrioritiesController::class, 'create'])->name('create');
            Route::post('/', [TicketPrioritiesController::class, 'store'])->name('store');
            Route::get('{priority}/edit', [TicketPrioritiesController::class, 'edit'])->name('edit');
            Route::put('{priority}', [TicketPrioritiesController::class, 'update'])->name('update');
            Route::delete('{priority}', [TicketPrioritiesController::class, 'destroy'])->name('destroy');
            Route::post('bulk-action', [TicketPrioritiesController::class, 'bulkAction'])->name('bulk-action');
        });

        // Statuses
        Route::prefix('statuses')->name('ticket-statuses.')->group(function () {
            Route::get('/', [TicketStatusesController::class, 'index'])->name('index');
            Route::get('create', [TicketStatusesController::class, 'create'])->name('create');
            Route::post('/', [TicketStatusesController::class, 'store'])->name('store');
            Route::get('{status}/edit', [TicketStatusesController::class, 'edit'])->name('edit');
            Route::put('{status}', [TicketStatusesController::class, 'update'])->name('update');
            Route::delete('{status}', [TicketStatusesController::class, 'destroy'])->name('destroy');
            Route::post('reorder', [TicketStatusesController::class, 'reorder'])->name('reorder');
            Route::post('bulk-action', [TicketStatusesController::class, 'bulkAction'])->name('bulk-action');
            Route::post('slug', [TicketStatusesController::class, 'ajaxSlug'])->name('ajax-slug');
        });

        // SLA policies
        Route::prefix('sla-policies')->name('ticket-sla-policies.')->group(function () {
            Route::get('/', [TicketSlaPoliciesController::class, 'index'])->name('index');
            Route::get('create', [TicketSlaPoliciesController::class, 'create'])->name('create');
            Route::post('/', [TicketSlaPoliciesController::class, 'store'])->name('store');
            Route::get('{policy}/edit', [TicketSlaPoliciesController::class, 'edit'])->name('edit');
            Route::put('{policy}', [TicketSlaPoliciesController::class, 'update'])->name('update');
            Route::patch('{policy}/toggle', [TicketSlaPoliciesController::class, 'toggle'])->name('toggle');
            Route::delete('{policy}', [TicketSlaPoliciesController::class, 'destroy'])->name('destroy');
            Route::post('bulk-action', [TicketSlaPoliciesController::class, 'bulkAction'])->name('bulk-action');
        });

        // Views
        Route::prefix('views')->name('ticket-views.')->group(function () {
            Route::get('/', [TicketViewsController::class, 'index'])->name('index');
            Route::get('create', [TicketViewsController::class, 'create'])->name('create');
            Route::post('/', [TicketViewsController::class, 'store'])->name('store');
            Route::get('{view}/edit', [TicketViewsController::class, 'edit'])->name('edit');
            Route::put('{view}', [TicketViewsController::class, 'update'])->name('update');
            Route::delete('{view}', [TicketViewsController::class, 'destroy'])->name('destroy');
            Route::post('reorder', [TicketViewsController::class, 'reorder'])->name('reorder');
            Route::post('bulk-action', [TicketViewsController::class, 'bulkAction'])->name('bulk-action');
        });

        // Email blacklist
        Route::prefix('blacklist')->name('ticket-blacklist.')->group(function () {
            Route::get('/', [TicketEmailBlacklistController::class, 'index'])->name('index');
            Route::get('history', [TicketEmailBlacklistController::class, 'history'])->name('history');
            Route::get('history/{hit}/preview', [TicketEmailBlacklistController::class, 'preview'])->name('history.preview');
            Route::post('/', [TicketEmailBlacklistController::class, 'store'])->name('store');
            Route::patch('{entry}/toggle', [TicketEmailBlacklistController::class, 'toggle'])->name('toggle');
            Route::delete('{entry}', [TicketEmailBlacklistController::class, 'destroy'])->name('destroy');
            Route::post('bulk-action', [TicketEmailBlacklistController::class, 'bulkAction'])->name('bulk-action');
        });

        // Automations
        Route::prefix('automations')->name('automations.')->group(function () {
            Route::get('/', [AutomationsController::class, 'index'])->name('index');
            Route::get('create', [AutomationsController::class, 'create'])->name('create');
            Route::post('/', [AutomationsController::class, 'store'])->name('store');
            Route::get('{automation}/edit', [AutomationsController::class, 'edit'])->name('edit');
            Route::put('{automation}', [AutomationsController::class, 'update'])->name('update');
            Route::delete('{automation}', [AutomationsController::class, 'destroy'])->name('destroy');
            Route::post('bulk-action', [AutomationsController::class, 'bulkAction'])->name('bulk-action');
        });

        // Macros
        Route::prefix('macros')->name('macros.')->group(function () {
            Route::get('/', [MacrosController::class, 'index'])->name('index');
            Route::get('create', [MacrosController::class, 'create'])->name('create');
            Route::post('/', [MacrosController::class, 'store'])->name('store');
            Route::get('{macro}/edit', [MacrosController::class, 'edit'])->name('edit');
            Route::put('{macro}', [MacrosController::class, 'update'])->name('update');
            Route::delete('{macro}', [MacrosController::class, 'destroy'])->name('destroy');
            Route::post('bulk-action', [MacrosController::class, 'bulkAction'])->name('bulk-action');
        });

        // Canales de correo (conexiones IMAP que generan tickets) — self-contained
        // en HelpdeskTickets; antes solo vivía en MailsSettings (ver
        // TicketEmailChannelsController).
        Route::prefix('email-channels')->name('email-channels.')->group(function () {
            Route::get('/', [TicketEmailChannelsController::class, 'index'])->name('index');
            // 'create' antes de cualquier '{channel}' para que no lo capture
            // el parámetro comodín.
            Route::get('create', [TicketEmailChannelsController::class, 'create'])->name('create');
            Route::post('/', [TicketEmailChannelsController::class, 'store'])->name('store');
            Route::get('{channel}/edit', [TicketEmailChannelsController::class, 'edit'])->name('edit');
            Route::put('{channel}', [TicketEmailChannelsController::class, 'update'])->name('update');
            Route::delete('{channel}', [TicketEmailChannelsController::class, 'destroy'])->name('destroy');
            // 'bulk-action' antes de cualquier '{channel}', igual que 'create'.
            Route::post('bulk-action', [TicketEmailChannelsController::class, 'bulkAction'])->name('bulk-action');
            Route::post('test', [TicketEmailChannelsController::class, 'test'])->name('test');
            Route::post('test-smtp', [TicketEmailChannelsController::class, 'testSmtp'])->name('test-smtp');
            Route::post('{channel}/sync', [TicketEmailChannelsController::class, 'sync'])->name('sync');
        });
    });
});

/*
 * Los endpoints puente con la bandeja (conversations/{c}/ticket y
 * ticket-detail) VIVÍAN AQUÍ, y este archivo entero va detrás de
 * role:super-admin|super-settings. La bandeja, en cambio, se sirve con
 * ['web','auth'] y decide por policies, así que un helpdesk-agent veía el
 * botón "Crear ticket" del hilo y recibía un 403 al enviarlo. Se han movido a
 * routes/conversation-bridge.php, con el mismo gate de rol amplio que
 * ticket-templates.php y el permiso fino (TicketPolicy) en el controlador.
 */

/*
 * Ticket reports endpoints. These were previously in Helpdesk's managers.php
 * pointing at Helpdesk\\ReportsController (which only queried tickets).
 * Owned by HelpdeskTickets so the queries vanish when the module is disabled.
 */
Route::prefix('reports')->name('manager.helpdesk.reports.')->group(function () {
    Route::get('/', [HelpdeskReportsController::class, 'index'])->name('index');
    Route::get('/export', [HelpdeskReportsController::class, 'export'])->name('export')->middleware('throttle:helpdesk-export');
});
