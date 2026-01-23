<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\Integrations\Integration;

class IntegrationPolicy
{
    /**
     * Determine whether the user can view any integrations.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the integration.
     */
    public function view(User $user, Integration $integration): bool
    {
        return $user->account_id === $integration->account_id;
    }

    /**
     * Determine whether the user can create integrations.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the integration.
     */
    public function update(User $user, Integration $integration): bool
    {
        return $user->account_id === $integration->account_id;
    }

    /**
     * Determine whether the user can delete the integration.
     */
    public function delete(User $user, Integration $integration): bool
    {
        return $user->account_id === $integration->account_id;
    }

    /**
     * Determine whether the user can restore the integration.
     */
    public function restore(User $user, Integration $integration): bool
    {
        return $user->account_id === $integration->account_id;
    }

    /**
     * Determine whether the user can permanently delete the integration.
     */
    public function forceDelete(User $user, Integration $integration): bool
    {
        return $user->account_id === $integration->account_id;
    }
}
