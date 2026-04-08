<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\TicketComment;

class TicketCommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_helpdesk') || $user->hasPermissionTo('view_tickets');
    }

    public function view(User $user, TicketComment $comment): bool
    {
        return $user->hasPermissionTo('manage_helpdesk') || $comment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('edit_tickets') || $user->hasPermissionTo('manage_helpdesk');
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
