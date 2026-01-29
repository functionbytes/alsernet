<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\SubAccount;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SubAccountPolicy
{
    use HandlesAuthorization;

    public function read(User $user, SubAccount $sub_account, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $user->admin->getPermission('sending_server_read') != 'no';
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }

    public function readAll(User $user, SubAccount $sub_account, $role)
    {
        switch ($role) {
            case 'admin':
                $can = $user->admin->getPermission('sending_server_read') == 'all';
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }

    public function delete(User $user, SubAccount $sub_account, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('sending_server_delete');
                $can = $ability == 'all'
                    || ($ability == 'own' && $user->admin->id == $sub_account->sendingServer->admin_id);
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }
}
