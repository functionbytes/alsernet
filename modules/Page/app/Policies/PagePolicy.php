<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\Page;

class PagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view the page listing.
     */
    public function viewAny(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.view');
    }

    /**
     * Determine if the user can view a specific page.
     */
    public function view(User $user, Page $page): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.view');
    }

    /**
     * Determine if the user can create pages.
     */
    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.create');
    }

    /**
     * Determine if the user can update a page.
     */
    public function update(User $user, Page $page): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.update');
    }

    /**
     * Determine if the user can delete a page.
     */
    public function delete(User $user, Page $page): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.delete');
    }

    /**
     * Determine if the user can restore a soft-deleted page.
     */
    public function restore(User $user, Page $page): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.delete');
    }

    /**
     * Determine if the user can permanently delete a page.
     */
    public function forceDelete(User $user, Page $page): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.delete');
    }

    /**
     * Determine if the user can publish or unpublish a page.
     */
    public function publish(User $user, Page $page): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.publish');
    }

    /**
     * Determine if the user can perform bulk actions on pages.
     *
     * Requires page.update because bulk operations (publish, unpublish, delete, restore)
     * all modify page state. Users without this permission must not reach bulkAction().
     */
    public function bulkAction(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.update');
    }

    /**
     * Determine if the user can duplicate a page.
     */
    public function duplicate(User $user, Page $page): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.create');
    }

    /**
     * Determine if the user can approve or reject page approvals.
     */
    public function approve(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $user->hasPermissionTo('page.approve');
    }

    /**
     * Check if the user is a super admin.
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-settings') || $user->hasRole('Super Admin');
    }
}
