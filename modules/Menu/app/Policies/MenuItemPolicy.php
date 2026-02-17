<?php

namespace Modules\Menu\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Menu\Models\MenuItem;

class MenuItemPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can create menu items.
     */
    public function create($user): bool
    {
        return $user->can('menu.edit');
    }

    /**
     * Determine if the user can update the menu item.
     */
    public function update($user, MenuItem $menuItem): bool
    {
        return $user->can('menu.edit');
    }

    /**
     * Determine if the user can delete the menu item.
     */
    public function delete($user, MenuItem $menuItem): bool
    {
        return $user->can('menu.edit');
    }

    /**
     * Determine if the user can reorder menu items.
     */
    public function reorder($user): bool
    {
        return $user->can('menu.edit');
    }
}
