<?php

namespace Modules\Locations\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Locations\Models\City;

class CityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('locations.cities.view');
    }

    public function view(User $user, City $city): bool
    {
        return $user->can('locations.cities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('locations.cities.create');
    }

    public function update(User $user, City $city): bool
    {
        return $user->can('locations.cities.update');
    }

    public function delete(User $user, City $city): bool
    {
        return $user->can('locations.cities.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('locations.cities.view');
    }
}
