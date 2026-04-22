<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\TicketTimeEntry;

class TimeEntryPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.update')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function delete(User $user, TicketTimeEntry $timeEntry): bool
    {
        return $user->hasRole('super-admin') || $timeEntry->user_id === $user->id;
    }
}
