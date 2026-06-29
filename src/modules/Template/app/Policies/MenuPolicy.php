<?php

namespace Modules\Template\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Template\Models\Menu;

class MenuPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any menus.
     */
    public function viewAny($user): bool
    {
        return $user->can('menu.view');
    }

    /**
     * Determine if the user can view the menu.
     */
    public function view($user, Menu $menu): bool
    {
        return $user->can('menu.view');
    }

    /**
     * Determine if the user can create menus.
     */
    public function create($user): bool
    {
        return $user->can('menu.create');
    }

    /**
     * Determine if the user can update the menu.
     */
    public function update($user, Menu $menu): bool
    {
        return $user->can('menu.edit');
    }

    /**
     * Determine if the user can delete the menu.
     */
    public function delete($user, Menu $menu): bool
    {
        return $user->can('menu.delete');
    }

    /**
     * Determine if the user can restore the menu.
     */
    public function restore($user, Menu $menu): bool
    {
        return $user->can('menu.restore');
    }

    /**
     * Determine if the user can permanently delete the menu.
     */
    public function forceDelete($user, Menu $menu): bool
    {
        return $user->can('menu.force-delete');
    }
}
