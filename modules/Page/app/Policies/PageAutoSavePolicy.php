<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\PageAutoSave;

class PageAutoSavePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('page.view');
    }

    public function view(User $user, PageAutoSave $autoSave): bool
    {
        return $user->can('page.view') && $autoSave->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('page.update');
    }

    public function update(User $user, PageAutoSave $autoSave): bool
    {
        return $user->can('page.update') && $autoSave->user_id === $user->id;
    }

    public function delete(User $user, PageAutoSave $autoSave): bool
    {
        return $user->can('page.update') && $autoSave->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->can('page.manage');
    }
}
