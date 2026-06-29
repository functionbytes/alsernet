<?php

namespace Modules\Locations\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Locations\Models\State;

class StatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('locations.states.view');
    }

    public function view(User $user, State $state): bool
    {
        return $user->can('locations.states.view');
    }

    public function create(User $user): bool
    {
        return $user->can('locations.states.create');
    }

    public function update(User $user, State $state): bool
    {
        return $user->can('locations.states.update');
    }

    public function delete(User $user, State $state): bool
    {
        return $user->can('locations.states.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('locations.states.view');
    }
}
