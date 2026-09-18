<?php

namespace Modules\Activity\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Activitylog\Models\Activity;

class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('activity.logs.view');
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->can('activity.logs.view');
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->can('activity.logs.delete');
    }

    public function export(User $user): bool
    {
        return $user->can('activity.logs.export');
    }

    public function viewAudit(User $user): bool
    {
        return $user->can('activity.audit.view');
    }
}
