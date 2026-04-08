<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\TicketNote;

class TicketNotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_helpdesk') || $user->hasPermissionTo('view_tickets');
    }

    public function view(User $user, TicketNote $note): bool
    {
        return $user->hasPermissionTo('manage_helpdesk') || $note->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('edit_tickets') || $user->hasPermissionTo('manage_helpdesk');
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
