<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\PageTag;

class PageTagPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('page.view');
    }

    public function view(User $user, PageTag $tag): bool
    {
        return $user->can('page.view');
    }

    public function create(User $user): bool
    {
        return $user->can('page.create');
    }

    public function update(User $user, PageTag $tag): bool
    {
        return $user->can('page.update');
    }

    public function delete(User $user, PageTag $tag): bool
    {
        return $user->can('page.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('page.manage');
    }
}
