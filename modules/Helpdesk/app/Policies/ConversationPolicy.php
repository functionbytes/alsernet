<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Conversation;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_conversations');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage_conversations')
            || $conversation->assignee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_conversations');
    }

    public function update(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage_conversations')
            || $conversation->assignee_id === $user->id;
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage_conversations');
    }

    public function restore(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('manage_conversations');
    }

    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }
}
