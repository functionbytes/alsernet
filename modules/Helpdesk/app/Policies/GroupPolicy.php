<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\Group;

class GroupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.groups.view');
    }

    public function view(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('helpdesk.groups.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.groups.create');
    }

    public function update(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('helpdesk.groups.update');
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->hasPermissionTo('helpdesk.groups.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.groups.manage');
    }
}
