<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\TicketTimeEntry;

class TimeEntryPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('edit_tickets') || $user->hasPermissionTo('manage_helpdesk');
    }

    public function delete(User $user, TicketTimeEntry $timeEntry): bool
    {
        return $user->hasRole('super-admin') || $timeEntry->user_id === $user->id;
    }
}
