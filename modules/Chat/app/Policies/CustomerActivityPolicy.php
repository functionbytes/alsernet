<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Customers\CustomerActivity;

class CustomerActivityPolicy
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
    public function view(User $user, CustomerActivity $customerActivity): bool
    {
        return $user->account_id === $customerActivity->account_id;
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
    public function update(User $user, CustomerActivity $customerActivity): bool
    {
        return $user->account_id === $customerActivity->account_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomerActivity $customerActivity): bool
    {
        return $user->account_id === $customerActivity->account_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CustomerActivity $customerActivity): bool
    {
        return $this->delete($user, $customerActivity);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CustomerActivity $customerActivity): bool
    {
        return $this->delete($user, $customerActivity);
    }
}
