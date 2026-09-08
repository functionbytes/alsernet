<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.view');
    }

    /**
     * Acciones sobre el conjunto de tickets, no sobre uno: hoy, exportar.
     *
     * `TicketExportController` la invoca con `authorize('manage', Ticket::class)`
     * desde que existe, pero el método NO estaba escrito. Laravel deniega
     * cuando la Policy existe y no implementa la habilidad, así que la
     * exportación devolvía 403 — a todo el mundo, incluido `super-admin`.
     * No se notó porque un `Gate::before` global concedía cualquier permiso al
     * rol `super-settings` y la Policy no llegaba a consultarse nunca; al
     * retirarse ese atajo (7-sep-2026) el agujero quedó a la vista.
     *
     * Exportar es leer en bloque, así que se pide el permiso de ver tickets.
     * A diferencia de `view()`, aquí NO vale ser el asignado: un CSV se lleva
     * también los tickets que no son de uno.
     */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($ticket->assignee_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('helpdesk.tickets.view')
            && $this->inScope($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->assignee_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('helpdesk.tickets.update')
            && $this->inScope($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.delete');
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.delete');
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.update')
            && $this->inScope($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($ticket->assignee_id === $user->id) {
            return true;
        }

        return ($user->hasPermissionTo('helpdesk.tickets.close') || $user->hasPermissionTo('helpdesk.tickets.update'))
            && $this->inScope($user, $ticket);
    }

    /**
     * Un agente sin helpdesk.tickets.manage solo actúa sobre lo suyo: lo que
     * tiene asignado (ya cubierto aparte en cada método de arriba, antes de
     * llamar a este helper) o lo que sea de un equipo al que pertenece.
     * helpdesk.tickets.manage —super-admin, super-settings, helpdesk-admin—
     * ve y actúa sobre cualquier ticket sin esta restricción.
     *
     * Antes el permiso base (view/update/close) abría CUALQUIER ticket de
     * CUALQUIER equipo: el mismo permiso que hace falta para trabajar en el
     * propio listado abría también el ajeno tecleando la URL.
     */
    private function inScope(User $user, Ticket $ticket): bool
    {
        if ($user->hasPermissionTo('helpdesk.tickets.manage')) {
            return true;
        }

        return $ticket->group_id !== null
            && in_array($ticket->group_id, TicketGroup::idsForUser($user->id), true);
    }

    public function resolve(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.resolve')
            || $this->close($user, $ticket);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $this->close($user, $ticket);
    }

    public function archive(User $user, Ticket $ticket): bool
    {
        return $this->close($user, $ticket);
    }

    public function merge(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.update');
    }

    public function watch(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }
}
