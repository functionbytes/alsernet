<?php

namespace Modules\User\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasPermissionTo('view-users');
    }

    public function view(User $authUser, User $user): bool
    {
        return $authUser->hasPermissionTo('view-users');
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('create-users');
    }

    public function update(User $authUser, User $user): bool
    {
        return $authUser->hasPermissionTo('edit-users');
    }

    public function delete(User $authUser, User $user): bool
    {
        return $authUser->hasPermissionTo('delete-users');
    }

    public function bulkAction(User $authUser): bool
    {
        return $authUser->hasPermissionTo('edit-users');
    }

    public function export(User $authUser): bool
    {
        return $authUser->hasPermissionTo('view-users');
    }

    public function impersonate(User $authUser, User $user): bool
    {
        return $authUser->hasPermissionTo('impersonate-users') && $authUser->id !== $user->id;
    }
}
