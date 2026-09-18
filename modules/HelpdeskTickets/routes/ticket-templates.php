<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketTemplatesController;

// Plantillas de ticket: generales (compartidas) + personales por usuario.
// Vive fuera de managers.php porque necesita un gate de rol mas amplio
// (tambien agentes de helpdesk, no solo super-admin/super-settings) — ver
// loadTicketTemplatesRoutes() en HelpdeskTicketsServiceProvider. Los nombres
// de ruta se mantienen bajo el prefijo "manager." para no romper los
// route() ya existentes en vistas/tests de cuando esto vivia en managers.php.
Route::post('ticket-templates/bulk', [TicketTemplatesController::class, 'bulkAction'])->name('manager.helpdesk.ticket-templates.bulk-action');
Route::post('ticket-templates/{ticketTemplate}/duplicate', [TicketTemplatesController::class, 'duplicate'])->name('manager.helpdesk.ticket-templates.duplicate');

Route::resource('ticket-templates', TicketTemplatesController::class)->names([
    'index' => 'manager.helpdesk.ticket-templates.index',
    'create' => 'manager.helpdesk.ticket-templates.create',
    'store' => 'manager.helpdesk.ticket-templates.store',
    'edit' => 'manager.helpdesk.ticket-templates.edit',
    'update' => 'manager.helpdesk.ticket-templates.update',
    'destroy' => 'manager.helpdesk.ticket-templates.destroy',
])->except(['show']);
