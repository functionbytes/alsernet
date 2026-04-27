<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\HelpCenterCategory;

class HelpCenterCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.helpcenter.categories.view');
    }

    public function view(User $user, HelpCenterCategory $helpCenterCategory): bool
    {
        return $user->hasPermissionTo('helpdesk.helpcenter.categories.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.helpcenter.categories.create');
    }

    public function update(User $user, HelpCenterCategory $helpCenterCategory): bool
    {
        return $user->hasPermissionTo('helpdesk.helpcenter.categories.update');
    }

    public function delete(User $user, HelpCenterCategory $helpCenterCategory): bool
    {
        return $user->hasPermissionTo('helpdesk.helpcenter.categories.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.helpcenter.categories.manage');
    }
}
