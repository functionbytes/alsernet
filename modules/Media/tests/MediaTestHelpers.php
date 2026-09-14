<?php

namespace Modules\Media\Tests;

use App\Models\User;
use Spatie\Permission\Models\Permission;

trait MediaTestHelpers
{
    protected function createUserWithMediaPermissions(array $permissions = ['media.view', 'media.create', 'media.update', 'media.delete']): User
    {
        foreach ($permissions as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    protected function createUserWithPermission(string $permission): User
    {
        Permission::findOrCreate($permission, 'web');

        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
