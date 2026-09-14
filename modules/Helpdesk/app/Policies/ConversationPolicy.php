<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\AgentInboxCapacity;
use Modules\Helpdesk\Models\Conversation;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.view');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        if (! $user->hasPermissionTo('helpdesk.conversations.view') && $conversation->assignee_id !== $user->id) {
            return false;
        }

        return $this->canAccessInbox($user, $conversation);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.create');
    }

    public function update(User $user, Conversation $conversation): bool
    {
        if (! $user->hasPermissionTo('helpdesk.conversations.update') && $conversation->assignee_id !== $user->id) {
            return false;
        }

        return $this->canAccessInbox($user, $conversation);
    }

    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.delete')
            && $this->canAccessInbox($user, $conversation);
    }

    public function restore(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('helpdesk.conversations.manage')
            && $this->canAccessInbox($user, $conversation);
    }

    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return $user->hasAnyRole(['super-admin']);
    }

    /**
     * Managers see all inboxes; agents are restricted to their assigned inboxes.
     */
    protected function canAccessInbox(User $user, Conversation $conversation): bool
    {
        if ($user->hasPermissionTo('helpdesk.manage')) {
            return true;
        }

        return AgentInboxCapacity::where('user_id', $user->id)
            ->where('inbox_id', $conversation->inbox_id)
            ->exists();
    }
}
