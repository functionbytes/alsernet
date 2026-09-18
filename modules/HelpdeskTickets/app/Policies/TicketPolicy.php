<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.view')
            || $ticket->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.update')
            || $ticket->assignee_id === $user->id;
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
        return $user->hasPermissionTo('helpdesk.tickets.update');
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.close')
            || $user->hasPermissionTo('helpdesk.tickets.update')
            || $ticket->assignee_id === $user->id;
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
