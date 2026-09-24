<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskErp\Http\Controllers\Managers\ErpTicketsController;

/*
 | Extensión "tickets": Tienda y Gestión en la vista de ticket — SOLO LECTURA.
 | La incluye el cargador de routes/managers.d con prefijo 'panel/helpdesk' y
 | middleware ['web', 'auth'].
 |
 |  GET erp/tickets/{ticket}/host   datos de contacto del cliente del ticket +
 |                                  HTML del tab oculto de la tienda
 */
Route::get('/erp/tickets/{ticket}/host', [ErpTicketsController::class, 'host'])
    ->whereNumber('ticket')
    ->middleware('throttle:120,1')
    ->name('manager.helpdesk.erp.tickets.host');
