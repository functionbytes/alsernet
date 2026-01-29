<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\Blacklist;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlacklistPolicy
{
    use HandlesAuthorization;

    public function read(User $user, Blacklist $blacklist, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('report_blacklist');
                $can = $ability == 'yes';
                break;
            case 'customer':
                $can = true;
                break;
        }

        return $can;
    }

    public function readAll(User $user, Blacklist $blacklist, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('report_blacklist');
                $can = $ability == 'yes';
                break;
            case 'customer':
                $can = false;
                break;
        }

        return $can;
    }

    public function create(User $user, Blacklist $blacklist, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('report_blacklist');
                $can = $ability == 'yes';
                break;
            case 'customer':
                $can = true;
                break;
        }

        return $can;
    }

    public function import(User $user, Blacklist $blacklist, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('report_blacklist');
                $can = $ability == 'yes';
                break;
            case 'customer':
                $can = true;
                break;
        }

        return $can;
    }

    public function importCancel(User $user, Blacklist $blacklist, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('report_blacklist');
                $can = $ability == 'yes';
                break;
            case 'customer':
                $can = true;
                break;
        }

        return $can;
    }

    public function update(User $user, Blacklist $blacklist, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('report_blacklist');
                $can = $ability == 'yes';
                break;
            case 'customer':
                $can = $user->customer->id == $blacklist->customer_id;
                break;
        }

        return $can;
    }

    public function delete(User $user, Blacklist $blacklist, $role)
    {
        switch ($role) {
            case 'admin':
                $ability = $user->admin->getPermission('report_blacklist');
                $can = $ability == 'yes';
                break;
            case 'customer':
                $can = $user->customer->id == $blacklist->customer_id;
                break;
        }

        return $can;
    }
}
