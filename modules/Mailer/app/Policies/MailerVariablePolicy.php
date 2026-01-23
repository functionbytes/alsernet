<?php

namespace Modules\Mailer\Policies;

use App\Models\User;
use Modules\Mailer\Models\MailerVariable;

class MailerVariablePolicy
{
    /**
     * Determine if the user can view any mailer variables
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('mailer.variables.view');
    }

    /**
     * Determine if the user can view a mailer variable
     */
    public function view(User $user, MailerVariable $variable): bool
    {
        return $user->hasPermissionTo('mailer.variables.view');
    }

    /**
     * Determine if the user can create mailer variables
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('mailer.variables.create');
    }

    /**
     * Determine if the user can update a mailer variable
     */
    public function update(User $user, MailerVariable $variable): bool
    {
        return $user->hasPermissionTo('mailer.variables.update');
    }

    /**
     * Determine if the user can delete a mailer variable
     */
    public function delete(User $user, MailerVariable $variable): bool
    {
        return $user->hasPermissionTo('mailer.variables.delete');
    }
}
