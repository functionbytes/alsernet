<?php

namespace Modules\HelpdeskTickets\Policies;

use App\Models\User;
use Modules\HelpdeskTickets\Models\TicketComment;

class TicketCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage')
            || $user->hasPermissionTo('helpdesk.tickets.view');
    }

    public function view(User $user, TicketComment $comment): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.manage')
            || $comment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.tickets.update')
            || $user->hasPermissionTo('helpdesk.tickets.manage');
    }

    public function update(User $user, TicketComment $comment): bool
    {
        return $user->hasRole('super-admin') || $comment->user_id === $user->id;
    }

    public function delete(User $user, TicketComment $comment): bool
    {
        return $user->hasRole('super-admin') || $comment->user_id === $user->id;
    }

    public function restore(User $user, TicketComment $comment): bool
    {
        return $user->hasRole('super-admin') || $comment->user_id === $user->id;
    }
}
