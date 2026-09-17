<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Modules\HelpdeskTickets\Models\Ticket;

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

/*
| helpdesk.ticket.{ticketId} — MISMO bug que el de arriba, encontrado el
| 14-sep-2026 diseñando la presencia del listado: TicketUpdated::
| broadcastOn() transmite en PrivateChannel('helpdesk.ticket.'.$id) (con
| punto, distinto del canal de PRESENCIA 'ticket.{ticketId}' de routes/
| channels.php en la raíz), pero nadie lo autorizaba aquí — cualquier
| suscripción moría con 403 en silencio, así que ese evento nunca llegaba a
| nadie aunque se emitiera bien. Mismo criterio que el canal de presencia:
| delega en TicketPolicy::view (admite también al agente asignado, no solo
| a quien tiene el permiso global).
*/
Broadcast::channel('helpdesk.ticket.{ticketId}', function ($user, int $ticketId) {
    $ticket = Ticket::find($ticketId);

    return $ticket && Gate::forUser($user)->allows('view', $ticket);
});
