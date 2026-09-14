<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Canales de broadcasting de HelpdeskTickets
|--------------------------------------------------------------------------
|
| helpdesk.tickets — canal común de la bandeja de gestión de tickets. Por él
| viaja TicketCreated (evento 'ticket.created'), que el listado usa para
| avisar de que han entrado tickets nuevos sin tener que recargar.
|
| El evento existía y se emitía desde el principio, pero el canal no estaba
| autorizado en ningún sitio: cualquier intento de suscribirse moría con un
| 403 en /broadcasting/auth, así que nadie lo escuchaba. Se exige el mismo
| permiso que para ver el listado — quien no puede abrir la pantalla tampoco
| debe recibir asuntos ni números de ticket por websocket.
|
*/

Broadcast::channel('helpdesk.tickets', function ($user) {
    return $user && $user->can('helpdesk.tickets.view');
});
