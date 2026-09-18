<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskTickets\Models\TicketStatus;

class TicketStatusPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function view(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function update(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function delete(User $user, TicketStatus $ticketStatus): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.tickets.manage');
    }
}
