<?php

namespace Modules\Mailing\Policies;

use Modules\Mailing\Models\Layout;
use Modules\Mailing\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LayoutPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Layout $item)
    {
        $ability = $user->admin->getPermission('layout_update');
        $can = $ability == 'yes';

        return $can;
    }
}
