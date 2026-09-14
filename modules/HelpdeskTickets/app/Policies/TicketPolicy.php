<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Setting;
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
        if (filter_var(Setting::get('tickets.restict_to_delete_ticket', false), FILTER_VALIDATE_BOOLEAN)
            && ! $user->hasPermissionTo('helpdesk.tickets.manage')) {
            return false;
        }

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
     * llamar a este helper), lo que sea de un equipo al que pertenece, o lo
     * que no tenga equipo asignado. helpdesk.tickets.manage —super-admin,
     * super-settings, helpdesk-admin— ve y actúa sobre cualquier ticket sin
     * esta restricción.
     *
     * Antes el permiso base (view/update/close) abría CUALQUIER ticket de
     * CUALQUIER equipo: el mismo permiso que hace falta para trabajar en el
     * propio listado abría también el ajeno tecleando la URL.
     *
     * 8-sep-2026: se añadió `group_id === null` como caso válido, a la vez
     * que TicketsCrudController::scopeToVisibleTickets() empezó a tratarlo
     * como bote compartido (ver ahí el porqué). Sin este cambio aquí, el
     * listado ya lo enseñaba pero cualquier acción sobre él (asignar, cerrar,
     * actualizar) se topaba con un 403 — la Policy se habría quedado más
     * restrictiva que el propio listado.
     */
    private function inScope(User $user, Ticket $ticket): bool
    {
        if ($user->hasPermissionTo('helpdesk.tickets.manage')) {
            return true;
        }

        if ($ticket->group_id === null) {
            return true;
        }

        return in_array($ticket->group_id, TicketGroup::idsForUser($user->id), true);
    }

    /**
     * "¿Este ticket está al alcance de este usuario?", independiente del
     * permiso de una acción concreta — asignado a él, o en su equipo (o
     * ticket sin equipo). Pensado para endpoints de lectura que exigen su
     * PROPIO permiso de entrada más fino que helpdesk.tickets.view (p. ej.
     * helpdesk.tickets.emails.view en TicketMailsController::templates(),
     * o el autocompletado de respuestas predefinidas en
     * CannedRepliesController::search()) pero necesitan de todos modos
     * bloquear el cruce entre equipos antes de devolver datos del ticket
     * (nombre/email/NIF/saldo ERP del cliente, interpolados). Usar
     * authorize('view', $ticket) ahí exigiría ADEMÁS helpdesk.tickets.view,
     * rompiendo el caso legítimo de un agente que solo tiene el permiso más
     * fino (detectado 14-sep-2026 corrigiendo la fuga real: un fix ingenuo
     * con authorize('view', ...) tumbaba
     * TicketMailsControllerTest::test_templates_includes_interpolated_subject...,
     * cuyo usuario a propósito solo tiene helpdesk.tickets.emails.view).
     */
    public function accessibleTo(User $user, Ticket $ticket): bool
    {
        return $ticket->assignee_id === $user->id || $this->inScope($user, $ticket);
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
