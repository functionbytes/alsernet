<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskTickets\Models\TicketCannedReply;

class TicketCannedReplyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function view(User $user, TicketCannedReply $cannedReply): bool
    {
        return $user->can('helpdesk.tickets.settings')
            || $cannedReply->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.tickets.settings');
    }

    public function update(User $user, TicketCannedReply $cannedReply): bool
    {
        return $user->can('helpdesk.tickets.settings')
            || $cannedReply->user_id === $user->id;
    }

    public function delete(User $user, TicketCannedReply $cannedReply): bool
    {
        return $user->can('helpdesk.tickets.settings')
            || $cannedReply->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.tickets.manage');
    }
}
