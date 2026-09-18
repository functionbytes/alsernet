<?php

namespace Modules\HelpdeskSocial\Policies;

use App\Models\User;
use Modules\HelpdeskSocial\Models\SocialAccount;

class SocialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function view(User $user, SocialAccount $account): bool
    {
        return $user->hasPermissionTo('helpdesksocial.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesksocial.accounts.manage');
    }

    public function update(User $user, SocialAccount $account): bool
    {
        return $user->hasPermissionTo('helpdesksocial.accounts.manage');
    }

    public function delete(User $user, SocialAccount $account): bool
    {
        return $user->hasPermissionTo('helpdesksocial.accounts.manage');
    }
}
