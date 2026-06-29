<?php

namespace Modules\Optimize\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OptimizePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'optimize.view',
            'optimize.update',
            'optimize.run-command',
            'optimize.run-all',
            'optimize.reset-stats',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
