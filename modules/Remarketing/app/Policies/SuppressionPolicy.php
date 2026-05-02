<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Suppression;

class SuppressionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.suppressions.view');
    }

    public function view(User $user, Suppression $suppression): bool
    {
        return $user->can('remarketing.suppressions.view') && $this->owns($user, $suppression);
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.suppressions.manage');
    }

    public function update(User $user, Suppression $suppression): bool
    {
        return $user->can('remarketing.suppressions.manage') && $this->owns($user, $suppression);
    }

    public function delete(User $user, Suppression $suppression): bool
    {
        return $user->can('remarketing.suppressions.manage') && $this->owns($user, $suppression);
    }

    public function manage(User $user): bool
    {
        return $user->can('remarketing.suppressions.manage');
    }

    protected function owns(User $user, Suppression $suppression): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        return $suppression->store?->user_id === $user->id;
    }
}
