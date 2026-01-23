<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\Contacts\ContactSegment;

class ContactSegmentPolicy
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
    public function view(User $user, ContactSegment $contactSegment): bool
    {
        return $contactSegment->account_id === $user->account_id;
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
    public function update(User $user, ContactSegment $contactSegment): bool
    {
        return $contactSegment->user_id === $user->id
            && $contactSegment->account_id === $user->account_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ContactSegment $contactSegment): bool
    {
        return $contactSegment->user_id === $user->id
            && $contactSegment->account_id === $user->account_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ContactSegment $contactSegment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ContactSegment $contactSegment): bool
    {
        return false;
    }
}
