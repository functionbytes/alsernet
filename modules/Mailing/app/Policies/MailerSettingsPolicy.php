<?php

namespace Modules\Mailer\Policies;

use App\Models\User;

class MailerSettingsPolicy
{
    public function configure(User $user): bool
    {
        // Super-admin siempre puede
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return false;
    }

    public function viewSettings(User $user): bool
    {
        return $this->configure($user);
    }

    public function manageTemplates(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

    }

    public function manageComponents(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

    }

    public function manageVariables(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

    }

    public function manageEndpoints(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

    }
}
