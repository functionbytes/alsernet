<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\RecurringTicket;

class RecurringTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function update(User $user, RecurringTicket $recurringTicket): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function delete(User $user, RecurringTicket $recurringTicket): bool
    {
        return $user->hasRole('super-admin');
    }
}
