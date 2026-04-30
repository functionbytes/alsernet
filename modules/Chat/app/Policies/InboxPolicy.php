<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Inbox\Inbox;

class InboxPolicy
{
    /**
     * Determine whether the user can view any inboxes.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the inbox.
     */
    public function view(User $user, Inbox $inbox): bool
    {
        return $user->account_id === $inbox->account_id;
    }

    /**
     * Determine whether the user can create inboxes.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the inbox.
     */
    public function update(User $user, Inbox $inbox): bool
    {
        return $user->account_id === $inbox->account_id;
    }

    /**
     * Determine whether the user can delete the inbox.
     */
    public function delete(User $user, Inbox $inbox): bool
    {
        return $user->account_id === $inbox->account_id;
    }

    /**
     * Determine whether the user can restore the inbox.
     */
    public function restore(User $user, Inbox $inbox): bool
    {
        return $user->account_id === $inbox->account_id;
    }

    /**
     * Determine whether the user can permanently delete the inbox.
     */
    public function forceDelete(User $user, Inbox $inbox): bool
    {
        return $user->account_id === $inbox->account_id;
    }
}
