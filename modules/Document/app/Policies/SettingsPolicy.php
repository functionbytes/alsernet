<?php

namespace Modules\Document\Policies;

use App\Models\User;

class SettingsPolicy
{
    public function configure(User $user): bool
    {
        // Super-admin siempre puede
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Managers pueden configurar documentos
        if ($user->hasRole('manager')) {
            return $user->can('documents.configure');
        }

        return false;
    }

    public function viewSettings(User $user): bool
    {
        return $this->configure($user);
    }

    public function manageTypes(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('documents.manage-types');
    }

    public function manageConditions(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('documents.manage-conditions');
    }

    public function manageSLAPolicies(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('documents.manage-sla-policies');
    }

    public function manageGroups(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('documents.manage-groups');
    }

    public function manageBlockades(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasRole('manager') && $user->can('documents.manage-blockades');
    }
}
