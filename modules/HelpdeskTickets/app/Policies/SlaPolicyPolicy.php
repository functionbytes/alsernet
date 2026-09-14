<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;

class SlaPolicyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function view(User $user, TicketSlaPolicy $slaPolicy): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function update(User $user, TicketSlaPolicy $slaPolicy): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function delete(User $user, TicketSlaPolicy $slaPolicy): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.tickets.manage');
    }
}
