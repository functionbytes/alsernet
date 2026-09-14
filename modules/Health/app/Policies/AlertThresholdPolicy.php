<?php

namespace Modules\Health\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Health\Models\AlertThreshold;

class AlertThresholdPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('health.alerts.view');
    }

    public function view(User $user, AlertThreshold $alertThreshold): bool
    {
        return $user->can('health.alerts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('health.alerts.create');
    }

    public function update(User $user, AlertThreshold $alertThreshold): bool
    {
        return $user->can('health.alerts.update');
    }

    public function delete(User $user, AlertThreshold $alertThreshold): bool
    {
        return $user->can('health.alerts.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('health.alerts.view');
    }
}
