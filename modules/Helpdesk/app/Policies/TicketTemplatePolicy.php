<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\TicketTemplate;

class TicketTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }

    public function update(User $user, TicketTemplate $ticketTemplate): bool
    {
        return $user->hasPermissionTo('manage_helpdesk');
    }

    public function delete(User $user, TicketTemplate $ticketTemplate): bool
    {
        return $user->hasRole('super-admin');
    }
}
