<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\PageLock;

class PageLockPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('page.view');
    }

    public function view(User $user, PageLock $lock): bool
    {
        return $user->can('page.view');
    }

    public function create(User $user): bool
    {
        return $user->can('page.update');
    }

    public function update(User $user, PageLock $lock): bool
    {
        return $user->can('page.update');
    }

    public function delete(User $user, PageLock $lock): bool
    {
        return $user->can('page.update') || $lock->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->can('page.manage');
    }
}
