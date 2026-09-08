<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Managers\ConversationTicketBridgeController;

/*
 * Puente entre la bandeja de Helpdesk y HelpdeskTickets: escalar una
 * conversación a ticket y consultar el detalle de un ticket desde el panel
 * derecho. Vive en este módulo para que Helpdesk no dependa de HelpdeskTickets
 * en la capa de routing, y en un archivo aparte de managers.php porque
 * necesita el gate de rol de la BANDEJA (agentes incluidos), no el de
 * configuración del módulo. El permiso fino lo pone TicketPolicy dentro del
 * controlador. URLs y nombres de ruta se conservan: el JS del inbox no cambia.
 */
Route::post('/conversations/{conversation}/ticket', [ConversationTicketBridgeController::class, 'create'])
    ->name('manager.helpdesk.conversations.ticket');

Route::get('/conversations/{conversation}/ticket-detail/{ticket}', [ConversationTicketBridgeController::class, 'show'])
    ->name('manager.helpdesk.conversations.ticket-detail');

/*
 * Fragmento del tab "Tickets" del panel derecho, con el mismo contrato que
 * /right-panel/{files,previous,activity} del core: devuelve HTML, no JSON.
 */
Route::get('/conversations/{conversation}/right-panel/tickets', [ConversationTicketBridgeController::class, 'ticketsTab'])
    ->name('manager.helpdesk.conversations.right-panel.tickets');

/*
 * Acciones del modal de detalle: resolver y auto-asignarse. Van aquí y no en
 * el CRUD de agente porque aquellas responden con back() —un 302 inútil para
 * el AJAX del modal— y porque la comprobación de que el ticket pertenece a la
 * conversación vive en este controlador.
 */
Route::post('/conversations/{conversation}/ticket-detail/{ticket}/action', [ConversationTicketBridgeController::class, 'action'])
    ->name('manager.helpdesk.conversations.ticket-action');
