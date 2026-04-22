<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\BulkTicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\MacroApplyController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\RecurringTicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\ReportsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\AutomationsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\MacrosController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCannedRepliesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketCategoriesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketGroupsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketSlaPoliciesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketStatusesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\Settings\TicketViewsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketCommentsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketNotesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketSearchController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketTemplatesController;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TimeEntriesController;

Route::group(['prefix' => ''], function () {

    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('manager.helpdesk.reports');

    // Advanced search
    Route::get('search', [TicketSearchController::class, 'index'])->name('manager.helpdesk.search');

    // Macros (apply to ticket)
    Route::get('/macros/available', [MacroApplyController::class, 'list'])->name('manager.helpdesk.macros.list');
    Route::post('/tickets/{ticket}/macros/{macro}/apply', [MacroApplyController::class, 'apply'])->name('manager.helpdesk.tickets.macros.apply');

    // Tickets bulk action
    Route::post('/tickets/bulk', [BulkTicketsController::class, 'handle'])->name('manager.helpdesk.tickets.bulk');
    Route::post('/tickets/bulk-reply', [TicketsController::class, 'bulkReply'])->name('manager.helpdesk.tickets.bulk-reply');

    // Tickets export
    Route::get('/tickets/export/{format}', [TicketsController::class, 'export'])->name('manager.helpdesk.tickets.export');

    // Tickets CRUD
    Route::get('/tickets', [TicketsController::class, 'index'])->name('manager.helpdesk.tickets.index');
    Route::get('/tickets/create', [TicketsController::class, 'create'])->name('manager.helpdesk.tickets.create');
    Route::post('/tickets', [TicketsController::class, 'store'])->name('manager.helpdesk.tickets.store');
    Route::get('/tickets/{ticket}', [TicketsController::class, 'show'])->name('manager.helpdesk.tickets.show');
    Route::get('/tickets/{ticket}/edit', [TicketsController::class, 'edit'])->name('manager.helpdesk.tickets.edit');
    Route::put('/tickets/{ticket}', [TicketsController::class, 'update'])->name('manager.helpdesk.tickets.update');
    Route::delete('/tickets/{ticket}', [TicketsController::class, 'destroy'])->name('manager.helpdesk.tickets.destroy');
    Route::post('/tickets/{ticket}/restore', [TicketsController::class, 'restore'])->name('manager.helpdesk.tickets.restore')->withTrashed();
    Route::delete('/tickets/{ticket}/force-delete', [TicketsController::class, 'forceDelete'])->name('manager.helpdesk.tickets.force-delete')->withTrashed();
    Route::post('/tickets/{ticket}/close', [TicketsController::class, 'close'])->name('manager.helpdesk.tickets.close');
    Route::post('/tickets/{ticket}/resolve', [TicketsController::class, 'resolve'])->name('manager.helpdesk.tickets.resolve');
    Route::post('/tickets/{ticket}/reopen', [TicketsController::class, 'reopen'])->name('manager.helpdesk.tickets.reopen');
    Route::post('/tickets/{ticket}/archive', [TicketsController::class, 'archive'])->name('manager.helpdesk.tickets.archive');
    Route::post('/tickets/{ticket}/unarchive', [TicketsController::class, 'unarchive'])->name('manager.helpdesk.tickets.unarchive');
    Route::post('/tickets/{ticket}/messages', [TicketsController::class, 'storeMessage'])->name('manager.helpdesk.tickets.messages.store');
    Route::post('/tickets/{ticket}/merge', [TicketsController::class, 'merge'])->name('manager.helpdesk.tickets.merge');
    Route::post('/tickets/{ticket}/link', [TicketsController::class, 'linkTicket'])->name('manager.helpdesk.tickets.link');
    Route::delete('/tickets/{ticket}/link/{linkId}', [TicketsController::class, 'unlinkTicket'])->name('manager.helpdesk.tickets.unlink');
    Route::post('/tickets/{ticket}/watch', [TicketsController::class, 'watch'])->name('manager.helpdesk.tickets.watch');
    Route::delete('/tickets/{ticket}/watch', [TicketsController::class, 'unwatch'])->name('manager.helpdesk.tickets.unwatch');
    Route::post('/tickets/{ticket}/sla/pause', [TicketsController::class, 'pauseSla'])->name('manager.helpdesk.tickets.sla.pause');
    Route::post('/tickets/{ticket}/sla/resume', [TicketsController::class, 'resumeSla'])->name('manager.helpdesk.tickets.sla.resume');
    Route::post('/tickets/{ticket}/typing', [TicketsController::class, 'typing'])->name('manager.helpdesk.tickets.typing');
    Route::get('/tickets/{ticket}/smart-replies', [TicketsController::class, 'smartReplies'])->name('manager.helpdesk.tickets.smart-replies');

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
    Route::get('/tickets/{ticket}/notes', [TicketNotesController::class, 'index'])->name('manager.helpdesk.tickets.notes.index');
    Route::post('/tickets/{ticket}/notes', [TicketNotesController::class, 'store'])->name('manager.helpdesk.tickets.notes.store');
    Route::get('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'show'])->name('manager.helpdesk.tickets.notes.show');
    Route::put('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'update'])->name('manager.helpdesk.tickets.notes.update');
    Route::delete('/tickets/{ticket}/notes/{note}', [TicketNotesController::class, 'destroy'])->name('manager.helpdesk.tickets.notes.destroy');
    Route::post('/tickets/{ticket}/notes/{note}/pin', [TicketNotesController::class, 'pin'])->name('manager.helpdesk.tickets.notes.pin');
    Route::post('/tickets/{ticket}/notes/{note}/color', [TicketNotesController::class, 'changeColor'])->name('manager.helpdesk.tickets.notes.color');
    Route::post('/tickets/{ticket}/notes/{note}/restore', [TicketNotesController::class, 'restore'])->name('manager.helpdesk.tickets.notes.restore');

    // Ticket templates
    Route::resource('ticket-templates', TicketTemplatesController::class)->names([
        'index' => 'manager.helpdesk.ticket-templates.index',
        'create' => 'manager.helpdesk.ticket-templates.create',
        'store' => 'manager.helpdesk.ticket-templates.store',
        'show' => 'manager.helpdesk.ticket-templates.show',
        'edit' => 'manager.helpdesk.ticket-templates.edit',
        'update' => 'manager.helpdesk.ticket-templates.update',
        'destroy' => 'manager.helpdesk.ticket-templates.destroy',
    ]);

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
