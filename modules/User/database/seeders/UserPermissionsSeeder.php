<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'impersonate-users',
            'manage-users',
            'bulk-action-users',
            'export-users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
