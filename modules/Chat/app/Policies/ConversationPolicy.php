<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Conversations\Conversation;

class ConversationPolicy
{
    /**
     * Determine whether the user can view any conversations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }

    /**
     * Determine whether the user can create conversations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the conversation.
     */
    public function update(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }

    /**
     * Determine whether the user can delete the conversation.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }

    /**
     * Determine whether the user can restore the conversation.
     */
    public function restore(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }

    /**
     * Determine whether the user can permanently delete the conversation.
     */
    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return $user->account_id === $conversation->account_id;
    }
}
