<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\PageCategory;

class PageCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('page.manage-categories');
    }

    public function view(User $user, PageCategory $category): bool
    {
        return $user->can('page.manage-categories');
    }

    public function create(User $user): bool
    {
        return $user->can('page.manage-categories');
    }

    public function update(User $user, PageCategory $category): bool
    {
        return $user->can('page.manage-categories');
    }

    public function delete(User $user, PageCategory $category): bool
    {
        return $user->can('page.manage-categories');
    }

    public function manage(User $user): bool
    {
        return $user->can('page.manage');
    }
}
