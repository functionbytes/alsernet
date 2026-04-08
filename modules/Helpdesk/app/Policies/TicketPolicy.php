<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Ticket;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_tickets');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('view_tickets')
            || $ticket->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_tickets');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('edit_tickets')
            || $ticket->assignee_id === $user->id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('delete_tickets');
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('delete_tickets');
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('edit_tickets');
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('edit_tickets')
            || $ticket->assignee_id === $user->id;
    }
}
