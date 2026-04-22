<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.view');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.view')
            || $conversation->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.create');
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.update')
            || $conversation->assignee_id === $user->id;
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.delete');
    }

    public function restore(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.manage');
    }

    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }
}
