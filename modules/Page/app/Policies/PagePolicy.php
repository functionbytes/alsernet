<?php

namespace Modules\Page\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Page\Models\Page;

class PagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view pages');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Page $page): bool
    {
        return $user->can('view pages');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create pages');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Page $page): bool
    {
        // User can edit if they have permission and either:
        // 1. They own the page
        // 2. They have settings permissions
        return $user->can('edit pages') &&
               ($page->user_id === $user->id || $user->hasRole('settings'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Page $page): bool
    {
        return $user->can('delete pages') &&
               ($page->user_id === $user->id || $user->hasRole('settings'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Page $page): bool
    {
        return $user->can('delete pages');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Page $page): bool
    {
        return $user->hasRole('settings');
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user, Page $page): bool
    {
        return $user->can('publish pages');
    }

    /**
     * Determine whether the user can unpublish the model.
     */
    public function unpublish(User $user, Page $page): bool
    {
        return $user->can('publish pages');
    }
}
