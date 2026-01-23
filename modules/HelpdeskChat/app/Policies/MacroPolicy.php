<?php

namespace Modules\HelpdeskChat\Policies;

use App\Models\User;
use Modules\HelpdeskChat\Models\Macro;

class MacroPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All users in the account can view macros
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Macro $macro): bool
    {
        // User can view/execute a macro if:
        // 1. They're in the same account
        // 2. AND (it's global OR they created it OR it's for their team)

        if ($user->account_id !== $macro->account_id) {
            return false;
        }

        // Global macros are visible to everyone
        if ($macro->visibility === Macro::VISIBILITY_GLOBAL) {
            return true;
        }

        // Personal macros are only visible to creator
        if ($macro->visibility === Macro::VISIBILITY_PERSONAL) {
            return $user->id === $macro->created_by_id;
        }

        // Team macros - check if user is in any team
        // For now, allow all team macros (can be enhanced to check team membership)
        if ($macro->visibility === Macro::VISIBILITY_TEAM) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All users can create macros
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Macro $macro): bool
    {
        // Must be in the same account
        if ($user->account_id !== $macro->account_id) {
            return false;
        }

        // Global macros can be edited by anyone in the account
        if ($macro->visibility === Macro::VISIBILITY_GLOBAL) {
            return true;
        }

        // Personal and team macros can only be edited by creator
        return $user->id === $macro->created_by_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Macro $macro): bool
    {
        // Must be in the same account
        if ($user->account_id !== $macro->account_id) {
            return false;
        }

        // Global macros can be deleted by anyone in the account
        if ($macro->visibility === Macro::VISIBILITY_GLOBAL) {
            return true;
        }

        // Personal and team macros can only be deleted by creator
        return $user->id === $macro->created_by_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Macro $macro): bool
    {
        return $this->delete($user, $macro);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Macro $macro): bool
    {
        return $this->delete($user, $macro);
    }
}
