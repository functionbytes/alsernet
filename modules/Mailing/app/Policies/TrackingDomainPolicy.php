<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\TrackingDomain;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrackingDomainPolicy
{
    use HandlesAuthorization;

    public function read(User $user, TrackingDomain $item, $role)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, TrackingDomain $item, $role)
    {
        return $user->customer->id == $item->customer_id;
    }

    public function delete(User $user, TrackingDomain $item, $role)
    {
        return $user->customer->id == $item->customer_id;
    }
}
