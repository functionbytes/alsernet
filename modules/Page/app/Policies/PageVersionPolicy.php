<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\PageVersion;

class PageVersionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('page.view');
    }

    public function view(User $user, PageVersion $version): bool
    {
        return $user->can('page.view');
    }

    public function create(User $user): bool
    {
        return $user->can('page.update');
    }

    public function update(User $user, PageVersion $version): bool
    {
        return $user->can('page.update');
    }

    public function delete(User $user, PageVersion $version): bool
    {
        return $user->can('page.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('page.manage');
    }
}
