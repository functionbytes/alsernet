<?php

namespace Modules\Locations\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Locations\Models\Country;

class CountryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('locations.countries.view');
    }

    public function view(User $user, Country $country): bool
    {
        return $user->can('locations.countries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('locations.countries.create');
    }

    public function update(User $user, Country $country): bool
    {
        return $user->can('locations.countries.update');
    }

    public function delete(User $user, Country $country): bool
    {
        return $user->can('locations.countries.delete');
    }

    public function manage(User $user): bool
    {
        return $user->can('locations.countries.view');
    }
}
