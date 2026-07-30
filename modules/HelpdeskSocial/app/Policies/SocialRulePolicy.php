<?php

namespace Modules\HelpdeskSocial\Policies;

use App\Models\User;
use Modules\HelpdeskSocial\Models\SocialRule;

class SocialRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function view(User $user, SocialRule $rule): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesksocial.rules.manage');
    }

    public function update(User $user, SocialRule $rule): bool
    {
        return $user->hasPermissionTo('helpdesksocial.rules.manage');
    }

    public function delete(User $user, SocialRule $rule): bool
    {
        return $user->hasPermissionTo('helpdesksocial.rules.manage');
    }
}
