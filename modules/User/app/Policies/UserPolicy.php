<?php

namespace Modules\User\Policies;

use App\Models\User;
use Modules\Role\Services\PrivilegeGuard;

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

    // 29-sep-2026: sin ser super-admin no se puede editar (contraseña, email,
    // roles) ni borrar a un usuario con rol privilegiado (super-admin, etc.).
    public function update(User $authUser, User $user): bool
    {
        return $authUser->hasPermissionTo('edit-users')
            && app(PrivilegeGuard::class)->canManageUser($authUser, $user);
    }

    public function delete(User $authUser, User $user): bool
    {
        return $authUser->hasPermissionTo('delete-users')
            && app(PrivilegeGuard::class)->canManageUser($authUser, $user);
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
        return $authUser->hasPermissionTo('impersonate-users') && $authUser->id !== $user->id
            && app(PrivilegeGuard::class)->canManageUser($authUser, $user);
    }
}
