<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\ApplyAiSuggestionController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\BulkTicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\ConversationTicketBridgeController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\HelpdeskReportsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\MacroApplyController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\RecurringTicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\ScheduledRepliesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\AutomationsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\MacrosController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCannedRepliesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCategoriesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCategoryFieldsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketGeneralSettingsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketGroupsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketSlaPoliciesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketStatusesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketViewsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\SuggestedArticlesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketAttachmentDownloadController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketCommentsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketExportController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketFollowupsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketLifecycleController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMailsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMailViewsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMessagingController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketNotesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketPresenceController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketsCrudController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketSearchController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketSideConversationsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketTemplatesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketTranslationController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TimeEntriesController;

Route::group(['prefix' => ''], function () {

    // Advanced search
    Route::get('search', [TicketSearchController::class, 'index'])->name('manager.helpdesk.search');

    // Macros (apply to ticket)
    Route::get('/macros/available', [MacroApplyController::class, 'list'])->name('manager.helpdesk.macros.list');
    Route::post('/tickets/{ticket}/macros/{macro}/apply', [MacroApplyController::class, 'apply'])->name('manager.helpdesk.tickets.macros.apply');

    // Artículos del centro de ayuda sugeridos al responder (deflexión)
    Route::get('/tickets/{ticket}/suggested-articles', [SuggestedArticlesController::class, 'index'])->name('manager.helpdesk.tickets.suggested-articles');

    // Traducción de texto del ticket (mensaje entrante / borrador de respuesta)
    Route::post('/tickets/{ticket}/translate', [TicketTranslationController::class, 'translate'])->name('manager.helpdesk.tickets.translate');

    // Aplicar sugerencia de IA (categoría / prioridad)
    Route::post('/tickets/{ticket}/apply-ai-suggestion', [ApplyAiSuggestionController::class, 'apply'])->name('manager.helpdesk.tickets.apply-ai-suggestion');

    // Recordatorios de seguimiento del ticket
    Route::post('/tickets/{ticket}/followups', [TicketFollowupsController::class, 'store'])->name('manager.helpdesk.tickets.followups.store');
    Route::delete('/tickets/{ticket}/followups/{followup}', [TicketFollowupsController::class, 'destroy'])->name('manager.helpdesk.tickets.followups.destroy');

    // Side conversations del ticket (hilos laterales privados)
    Route::get('/tickets/{ticket}/side-conversations', [TicketSideConversationsController::class, 'index'])->name('manager.helpdesk.tickets.side-conversations.index');
    Route::post('/tickets/{ticket}/side-conversations', [TicketSideConversationsController::class, 'store'])->name('manager.helpdesk.tickets.side-conversations.store');
    Route::post('/tickets/{ticket}/side-conversations/{sideConversation}/messages', [TicketSideConversationsController::class, 'addMessage'])->name('manager.helpdesk.tickets.side-conversations.messages.store');
    Route::post('/tickets/{ticket}/side-conversations/{sideConversation}/close', [TicketSideConversationsController::class, 'close'])->name('manager.helpdesk.tickets.side-conversations.close');

    // Presencia de agentes en el ticket (agent collision)
    Route::post('/tickets/{ticket}/presence', [TicketPresenceController::class, 'heartbeat'])->name('manager.helpdesk.tickets.presence.heartbeat');
    Route::delete('/tickets/{ticket}/presence', [TicketPresenceController::class, 'leave'])->name('manager.helpdesk.tickets.presence.leave');

    // Respuestas programadas del ticket (send later)
    Route::get('/tickets/{ticket}/scheduled-replies', [ScheduledRepliesController::class, 'index'])->name('manager.helpdesk.tickets.scheduled-replies.index');
    Route::post('/tickets/{ticket}/scheduled-replies', [ScheduledRepliesController::class, 'store'])->name('manager.helpdesk.tickets.scheduled-replies.store');
    Route::delete('/tickets/{ticket}/scheduled-replies/{scheduledReply}', [ScheduledRepliesController::class, 'destroy'])->name('manager.helpdesk.tickets.scheduled-replies.destroy');

    // Tickets bulk action
    Route::post('/tickets/bulk', [BulkTicketsController::class, 'handle'])->name('manager.helpdesk.tickets.bulk');

    // Tickets export
    Route::get('/tickets/export/{format}', [TicketExportController::class, 'export'])->name('manager.helpdesk.tickets.export');

    // Emails enviados — bandeja global (todos los tickets), no confundir con
    // el widget de hasta 30 filas dentro de la ficha de un ticket concreto.
    // Ocupa la URL /tickets "pelada" a petición explícita (sustituye al
    // listado de tickets ahí); el listado se mudó a /tickets/list. Los
    // nombres de ruta NO cambian, así que todo lo que ya llama a
    // route('manager.helpdesk.tickets.emails.*') / route('...tickets.index')
    // sigue funcionando igual sin tocar nada más.
    Route::get('/tickets', [TicketMailsController::class, 'index'])->name('manager.helpdesk.tickets.emails.index');
    Route::get('/tickets/emails/export', [TicketMailsController::class, 'export'])->name('manager.helpdesk.tickets.emails.export');
    Route::get('/tickets/emails/templates', [TicketMailsController::class, 'templates'])->name('manager.helpdesk.tickets.emails.templates');
    Route::get('/tickets/emails/views', [TicketMailViewsController::class, 'index'])->name('manager.helpdesk.tickets.emails.views.index');
    Route::post('/tickets/emails/views', [TicketMailViewsController::class, 'store'])->name('manager.helpdesk.tickets.emails.views.store');
    Route::delete('/tickets/emails/views/{view}', [TicketMailViewsController::class, 'destroy'])->name('manager.helpdesk.tickets.emails.views.destroy');
    Route::post('/tickets/emails', [TicketMailsController::class, 'store'])->name('manager.helpdesk.tickets.emails.store');
    Route::post('/tickets/emails/bulk', [TicketMailsController::class, 'bulk'])->name('manager.helpdesk.tickets.emails.bulk');
    Route::get('/tickets/emails/{mail}', [TicketMailsController::class, 'data'])->name('manager.helpdesk.tickets.emails.data');
    Route::post('/tickets/emails/{mail}/resend', [TicketMailsController::class, 'resend'])->name('manager.helpdesk.tickets.emails.resend');
    Route::patch('/tickets/emails/{mail}/tags', [TicketMailsController::class, 'updateTags'])->name('manager.helpdesk.tickets.emails.tags');
    Route::post('/tickets/emails/{mail}/translate', [TicketMailsController::class, 'translate'])->name('manager.helpdesk.tickets.emails.translate');
    Route::get('/tickets/emails/{mail}/summary', [TicketMailsController::class, 'summary'])->name('manager.helpdesk.tickets.emails.summary');
    Route::delete('/tickets/emails/{mail}', [TicketMailsController::class, 'destroy'])->name('manager.helpdesk.tickets.emails.destroy');

    // Tickets CRUD (listado movido a /tickets/list — ver comentario arriba)
    Route::get('/tickets/list', [TicketsCrudController::class, 'index'])->name('manager.helpdesk.tickets.index');
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
    Route::get('/tickets/ops', [TicketsCrudController::class, 'ops'])->name('manager.helpdesk.tickets.ops');
    Route::get('/tickets/workload', [TicketsCrudController::class, 'workload'])->name('manager.helpdesk.tickets.workload');
    Route::post('/tickets/workload/distribute', [TicketsCrudController::class, 'distributeUnassigned'])->name('manager.helpdesk.tickets.workload.distribute');
    Route::get('/tickets/{ticket}', [TicketsCrudController::class, 'show'])->name('manager.helpdesk.tickets.show');
    // Ficha completa (side-conversations, registro de horas, fusión, enlaces,
    // historial): funciones que el panel superpuesto de /tickets aún no cubre.
    Route::get('/tickets/{ticket}/full', [TicketsCrudController::class, 'showFull'])->name('manager.helpdesk.tickets.show-full');
    // JSON de detalle para el panel de "Gestión de tickets" (Fase B): hilo,
    // actividad, archivos y correo — mismo patrón que
    // manager.helpdesk.tickets.emails.data.
    Route::get('/tickets/{ticket}/data', [TicketsCrudController::class, 'data'])->name('manager.helpdesk.tickets.data');
    Route::get('/tickets/{ticket}/summary', [TicketsCrudController::class, 'summary'])->name('manager.helpdesk.tickets.summary');
    Route::patch('/tickets/{ticket}/tags', [TicketsCrudController::class, 'tags'])->name('manager.helpdesk.tickets.tags');
    Route::get('/tickets/{ticket}/edit', [TicketsCrudController::class, 'edit'])->name('manager.helpdesk.tickets.edit');
    Route::put('/tickets/{ticket}', [TicketsCrudController::class, 'update'])->name('manager.helpdesk.tickets.update');
    Route::delete('/tickets/{ticket}', [TicketsCrudController::class, 'destroy'])->name('manager.helpdesk.tickets.destroy');
    Route::post('/tickets/{ticket}/restore', [TicketsCrudController::class, 'restore'])->name('manager.helpdesk.tickets.restore')->withTrashed();
    Route::delete('/tickets/{ticket}/force-delete', [TicketsCrudController::class, 'forceDelete'])->name('manager.helpdesk.tickets.force-delete')->withTrashed();

    // Ticket lifecycle
    Route::post('/tickets/{ticket}/close', [TicketLifecycleController::class, 'close'])->name('manager.helpdesk.tickets.close');
    Route::post('/tickets/{ticket}/resolve', [TicketLifecycleController::class, 'resolve'])->name('manager.helpdesk.tickets.resolve');
    Route::post('/tickets/{ticket}/reopen', [TicketLifecycleController::class, 'reopen'])->name('manager.helpdesk.tickets.reopen');
    Route::post('/tickets/{ticket}/archive', [TicketLifecycleController::class, 'archive'])->name('manager.helpdesk.tickets.archive');
    Route::post('/tickets/{ticket}/csat/send', [TicketsCrudController::class, 'sendCsatSurvey'])->name('manager.helpdesk.tickets.csat.send');
    Route::post('/tickets/{ticket}/unarchive', [TicketLifecycleController::class, 'unarchive'])->name('manager.helpdesk.tickets.unarchive');
    Route::post('/tickets/{ticket}/merge', [TicketLifecycleController::class, 'merge'])->name('manager.helpdesk.tickets.merge');
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

    // Ticket templates
    Route::resource('ticket-templates', TicketTemplatesController::class)->names([
        'index' => 'manager.helpdesk.ticket-templates.index',
        'create' => 'manager.helpdesk.ticket-templates.create',
        'store' => 'manager.helpdesk.ticket-templates.store',
        'edit' => 'manager.helpdesk.ticket-templates.edit',
        'update' => 'manager.helpdesk.ticket-templates.update',
        'destroy' => 'manager.helpdesk.ticket-templates.destroy',
    ])->except(['show']);

    // Recurring tickets
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
    Route::get('settings/tickets/general', [TicketGeneralSettingsController::class, 'index'])->name('manager.helpdesk.settings.tickets.general');
    Route::put('settings/tickets/general', [TicketGeneralSettingsController::class, 'update'])->name('manager.helpdesk.settings.tickets.general.update');

    // Ticket settings
    Route::prefix('settings/tickets')->name('manager.helpdesk.settings.')->group(function () {

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
        });

        // Canned replies
        Route::prefix('canned-replies')->name('ticket-canned-replies.')->group(function () {
            Route::get('/', [TicketCannedRepliesController::class, 'index'])->name('index');
            Route::get('create', [TicketCannedRepliesController::class, 'create'])->name('create');
            Route::post('/', [TicketCannedRepliesController::class, 'store'])->name('store');
            Route::get('{reply}/edit', [TicketCannedRepliesController::class, 'edit'])->name('edit');
            Route::put('{reply}', [TicketCannedRepliesController::class, 'update'])->name('update');
            Route::delete('{reply}', [TicketCannedRepliesController::class, 'destroy'])->name('destroy');
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
        });

        // Automations
        Route::prefix('automations')->name('automations.')->group(function () {
            Route::get('/', [AutomationsController::class, 'index'])->name('index');
            Route::get('create', [AutomationsController::class, 'create'])->name('create');
            Route::post('/', [AutomationsController::class, 'store'])->name('store');
            Route::get('{automation}/edit', [AutomationsController::class, 'edit'])->name('edit');
            Route::put('{automation}', [AutomationsController::class, 'update'])->name('update');
            Route::delete('{automation}', [AutomationsController::class, 'destroy'])->name('destroy');
        });

        // Macros
        Route::prefix('macros')->name('macros.')->group(function () {
            Route::get('/', [MacrosController::class, 'index'])->name('index');
            Route::get('create', [MacrosController::class, 'create'])->name('create');
            Route::post('/', [MacrosController::class, 'store'])->name('store');
            Route::get('{macro}/edit', [MacrosController::class, 'edit'])->name('edit');
            Route::put('{macro}', [MacrosController::class, 'update'])->name('update');
            Route::delete('{macro}', [MacrosController::class, 'destroy'])->name('destroy');
        });
    });
});

/*
 * Bridge endpoints between Helpdesk's inbox UI and HelpdeskTickets.
 * Routes registered in this module so Helpdesk does not import HelpdeskTickets.
 * URLs and route names are kept identical to the previous Helpdesk-owned ones
 * for transparent migration (frontend keeps calling the same route() helpers).
 */
Route::post('/conversations/{conversation}/ticket', [ConversationTicketBridgeController::class, 'create'])
    ->name('manager.helpdesk.conversations.ticket');
Route::get('/conversations/{conversation}/ticket-detail/{ticket}', [ConversationTicketBridgeController::class, 'show'])
    ->name('manager.helpdesk.conversations.ticket-detail');

/*
 * Ticket reports endpoints. These were previously in Helpdesk's managers.php
 * pointing at Helpdesk\\ReportsController (which only queried tickets).
 * Owned by HelpdeskTickets so the queries vanish when the module is disabled.
 */
Route::prefix('reports')->name('manager.helpdesk.reports.')->group(function () {
    Route::get('/', [HelpdeskReportsController::class, 'index'])->name('index');
    Route::get('/export', [HelpdeskReportsController::class, 'export'])->name('export')->middleware('throttle:helpdesk-export');
});
