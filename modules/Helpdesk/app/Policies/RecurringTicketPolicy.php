<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\RecurringTicket;

class RecurringTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }

    public function update(User $user, RecurringTicket $recurringTicket): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }

    public function delete(User $user, RecurringTicket $recurringTicket): bool
    {
        return $user->hasRole('super-admin');
    }
}
