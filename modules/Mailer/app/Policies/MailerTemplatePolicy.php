<?php

namespace Modules\Mailer\Policies;

use App\Models\User;
use Modules\Mailer\Models\MailerTemplate;

class MailerTemplatePolicy
{
    /**
     * Determine if the user can view any mailer templates
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('mailer.templates.view');
    }

    /**
     * Determine if the user can view a mailer template
     */
    public function view(User $user, MailerTemplate $template): bool
    {
        return $user->hasPermissionTo('mailer.templates.view');
    }

    /**
     * Determine if the user can create mailer templates
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('mailer.templates.create');
    }

    /**
     * Determine if the user can update a mailer template
     */
    public function update(User $user, MailerTemplate $template): bool
    {
        return $user->hasPermissionTo('mailer.templates.update');
    }

    /**
     * Determine if the user can delete a mailer template
     */
    public function delete(User $user, MailerTemplate $template): bool
    {
        return $user->hasPermissionTo('mailer.templates.delete');
    }

    /**
     * Determine if the user can preview a mailer template
     */
    public function preview(User $user, MailerTemplate $template): bool
    {
        return $user->hasPermissionTo('mailer.templates.preview');
    }

    /**
     * Determine if the user can manage mailer templates
     */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('mailer.manage');
    }
}
