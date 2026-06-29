<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\PageApproval;

class PageApprovalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('page.approve');
    }

    public function view(User $user, PageApproval $approval): bool
    {
        return $user->can('page.approve');
    }

    public function create(User $user): bool
    {
        return $user->can('page.update');
    }

    public function update(User $user, PageApproval $approval): bool
    {
        return $user->can('page.approve');
    }

    public function delete(User $user, PageApproval $approval): bool
    {
        return $user->can('page.approve');
    }

    public function approve(User $user): bool
    {
        return $user->can('page.approve');
    }

    public function manage(User $user): bool
    {
        return $user->can('page.manage');
    }
}
