<?php

namespace Modules\Mailer\Policies;

use App\Models\User;
use Modules\Mailer\Models\MailerLayout;

class MailerComponentPolicy
{
    /**
     * Determine if the user can view any mailer components
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('mailer.components.view');
    }

    /**
     * Determine if the user can view a mailer component
     */
    public function view(User $user, MailerLayout $component): bool
    {
        return $user->hasPermissionTo('mailer.components.view');
    }

    /**
     * Determine if the user can create mailer components
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('mailer.components.create');
    }

    /**
     * Determine if the user can update a mailer component
     */
    public function update(User $user, MailerLayout $component): bool
    {
        return $user->hasPermissionTo('mailer.components.update');
    }

    /**
     * Determine if the user can delete a mailer component
     */
    public function delete(User $user, MailerLayout $component): bool
    {
        return $user->hasPermissionTo('mailer.components.delete');
    }

    /**
     * Determine if the user can preview a mailer component
     */
    public function preview(User $user, MailerLayout $component): bool
    {
        return $user->hasPermissionTo('mailer.components.preview');
    }
}
