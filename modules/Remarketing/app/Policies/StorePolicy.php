<?php

namespace Modules\Remarketing\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Remarketing\Models\Store;

class StorePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('remarketing.stores.view');
    }

    public function view(User $user, Store $store): bool
    {
        return $user->can('remarketing.stores.view') && $this->ownsStore($user, $store);
    }

    public function create(User $user): bool
    {
        return $user->can('remarketing.stores.create');
    }

    public function update(User $user, Store $store): bool
    {
        return $user->can('remarketing.stores.update') && $this->ownsStore($user, $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->can('remarketing.stores.delete') && $this->ownsStore($user, $store);
    }

    public function manage(User $user): bool
    {
        return $user->can('remarketing.manage');
    }

    protected function ownsStore(User $user, Store $store): bool
    {
        if ($user->can('remarketing.manage')) {
            return true;
        }

        return $store->user_id === $user->id;
    }
}
