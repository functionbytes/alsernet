<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Customers\CustomerNote;

class CustomerNotePolicy
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
    public function view(User $user, CustomerNote $customerNote): bool
    {
        return $user->account_id === $customerNote->account_id;
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
    public function update(User $user, CustomerNote $customerNote): bool
    {
        return $user->account_id === $customerNote->account_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomerNote $customerNote): bool
    {
        return $user->account_id === $customerNote->account_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomerNote $customerNote): bool
    {
        return $this->delete($user, $customerNote);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomerNote $customerNote): bool
    {
        return $this->delete($user, $customerNote);
    }
}
