<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\TicketTemplate;

class TicketTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function update(User $user, TicketTemplate $ticketTemplate): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function delete(User $user, TicketTemplate $ticketTemplate): bool
    {
        return $user->hasRole('super-admin');
    }
}
