<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\Currency;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CurrencyPolicy
{
    use HandlesAuthorization;

    public function read(User $user, Currency $item)
    {
        $can = $user->admin->getPermission('currency_read') != 'no';

        return $can;
    }

    public function readAll(User $user, Currency $item)
    {
        $can = $user->admin->getPermission('currency_read') == 'all';

        return $can;
    }

    public function create(User $user, Currency $item)
    {
        $can = $user->admin->getPermission('currency_create') == 'yes';

        return $can;
    }

    public function update(User $user, Currency $item)
    {
        $ability = $user->admin->getPermission('currency_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function delete(User $user, Currency $item)
    {
        $ability = $user->admin->getPermission('currency_delete');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function disable(User $user, Currency $item)
    {
        $ability = $user->admin->getPermission('currency_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can && $item->status != 'inactive';
    }

    public function enable(User $user, Currency $item)
    {
        $ability = $user->admin->getPermission('currency_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can && $item->status != 'active';
    }
}
