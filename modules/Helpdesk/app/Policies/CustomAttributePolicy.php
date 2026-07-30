<?php

namespace Modules\Helpdesk\Policies;

use App\Models\User;
use Modules\Helpdesk\Models\CustomAttribute;

class CustomAttributePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.attributes.view');
    }

    public function view(User $user, CustomAttribute $customAttribute): bool
    {
        return $user->hasPermissionTo('helpdesk.attributes.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.attributes.create');
    }

    public function update(User $user, CustomAttribute $customAttribute): bool
    {
        return $user->hasPermissionTo('helpdesk.attributes.update');
    }

    public function delete(User $user, CustomAttribute $customAttribute): bool
    {
        return $user->hasPermissionTo('helpdesk.attributes.delete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('helpdesk.attributes.manage');
    }
}
