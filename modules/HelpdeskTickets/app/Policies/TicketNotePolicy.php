<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\TicketNote;

class TicketNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage')
            || $user->hasPermissionTo('helpdesk.tickets.view');
    }

    public function view(User $user, TicketNote $note): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage')
            || $note->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.update')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function update(User $user, TicketNote $note): bool
    {
        return $user->hasRole('super-admin') || $note->user_id === $user->id;
    }

    public function delete(User $user, TicketNote $note): bool
    {
        return $user->hasRole('super-admin') || $note->user_id === $user->id;
    }

    public function restore(User $user, TicketNote $note): bool
    {
        return $user->hasRole('super-admin') || $note->user_id === $user->id;
    }
}
