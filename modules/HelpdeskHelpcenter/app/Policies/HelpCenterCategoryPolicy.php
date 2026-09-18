<?php

namespace Modules\HelpdeskHelpcenter\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\HelpdeskHelpcenter\Models\HelpCenterCategory;

class HelpCenterCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('helpdesk.helpcenter.categories.view');
    }

    public function view(User $user, HelpCenterCategory $category): bool
    {
        return $user->can('helpdesk.helpcenter.categories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('helpdesk.helpcenter.categories.create');
    }

    public function update(User $user, HelpCenterCategory $category): bool
    {
        return $user->can('helpdesk.helpcenter.categories.update');
    }

    public function delete(User $user, HelpCenterCategory $category): bool
    {
        return $user->can('helpdesk.helpcenter.categories.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('helpdesk.helpcenter.categories.manage');
    }
}
