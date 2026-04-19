<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AuthPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'auth.impersonate',
            'auth.settings.view',
            'auth.settings.update',
            'auth.audit.view',
            'auth.devices.manage',
            'auth.api-tokens.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
