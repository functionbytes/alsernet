<?php

namespace Modules\Auth\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Auth\Models\ImpersonationLog;

class ImpersonationLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('auth.audit.view');
    }

    public function view(User $user, ImpersonationLog $log): bool
    {
        return $user->can('auth.audit.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ImpersonationLog $log): bool
    {
        return false;
    }

    public function delete(User $user, ImpersonationLog $log): bool
    {
        return $user->can('auth.audit.view');
    }

    public function manage(User $user): bool
    {
        return $user->can('auth.audit.view');
    }
}
