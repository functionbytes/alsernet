<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Conversations\ConversationMessage;

class MessagePolicy
{
    /**
     * Determine whether the user can view any messages.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the message.
     */
    public function view(User $user, ConversationMessage $message): bool
    {
        return $user->account_id === $message->account_id;
    }

    /**
     * Determine whether the user can create messages.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the message.
     */
    public function update(User $user, ConversationMessage $message): bool
    {
        return $user->account_id === $message->account_id;
    }

    /**
     * Determine whether the user can delete the message.
     */
    public function delete(User $user, ConversationMessage $message): bool
    {
        return $user->account_id === $message->account_id;
    }

    /**
     * Determine whether the user can restore the message.
     */
    public function restore(User $user, ConversationMessage $message): bool
    {
        return $this->delete($user, $message);
    }

    /**
     * Determine whether the user can permanently delete the message.
     */
    public function forceDelete(User $user, ConversationMessage $message): bool
    {
        return $this->delete($user, $message);
    }
}
