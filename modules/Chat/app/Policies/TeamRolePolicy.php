<?php

namespace Modules\Chat\Policies;

use App\Models\User;
use Modules\Chat\Models\Teams\TeamRole;

class TeamRolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('settings.view') || $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TeamRole $teamRole): bool
    {
        return $user->account_id === $teamRole->account_id &&
               ($user->hasPermission('settings.view') || $user->isAdmin());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('settings.manage') || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TeamRole $teamRole): bool
    {
        return $user->account_id === $teamRole->account_id &&
               ($user->hasPermission('settings.manage') || $user->isAdmin());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TeamRole $teamRole): bool
    {
        return $user->account_id === $teamRole->account_id &&
               ($user->hasPermission('settings.manage') || $user->isAdmin());
    }
}
