<?php

namespace Modules\HelpdeskSocial\Policies;

use App\Models\User;
use Modules\HelpdeskSocial\Models\SocialTemplate;

class SocialTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function view(User $user, SocialTemplate $template): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesksocial.manage-templates');
    }

    public function update(User $user, SocialTemplate $template): bool
    {
        return $user->hasPermissionTo('helpdesksocial.manage-templates');
    }

    public function delete(User $user, SocialTemplate $template): bool
    {
        return $user->hasPermissionTo('helpdesksocial.manage-templates');
    }
}
