<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\SavedContactFilter;

class SavedContactFilterPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SavedContactFilter $savedContactFilter): bool
    {
        // Users can view their own filters or public filters from their account
        return $savedContactFilter->account_id === $user->account_id
            && ($savedContactFilter->user_id === $user->id || $savedContactFilter->is_public);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SavedContactFilter $savedContactFilter): bool
    {
        // Only the owner can update their filter
        return $savedContactFilter->user_id === $user->id
            && $savedContactFilter->account_id === $user->account_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavedContactFilter $savedContactFilter): bool
    {
        // Only the owner can delete their filter
        return $savedContactFilter->user_id === $user->id
            && $savedContactFilter->account_id === $user->account_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SavedContactFilter $savedContactFilter): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SavedContactFilter $savedContactFilter): bool
    {
        return false;
    }
}
