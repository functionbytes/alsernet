<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Customers\CustomerSegment;

class CustomerSegmentPolicy
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
    public function view(User $user, CustomerSegment $segment): bool
    {
        return $user->account_id === $segment->account_id;
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
    public function update(User $user, CustomerSegment $segment): bool
    {
        return $user->account_id === $segment->account_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomerSegment $segment): bool
    {
        return $user->account_id === $segment->account_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomerSegment $segment): bool
    {
        return $this->delete($user, $segment);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomerSegment $segment): bool
    {
        return $this->delete($user, $segment);
    }
}
