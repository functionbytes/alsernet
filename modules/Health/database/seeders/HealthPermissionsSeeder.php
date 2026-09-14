<?php

namespace Modules\Health\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HealthPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'health.view',
            'health.history.view',
            'health.history.delete',
            'health.alerts.view',
            'health.alerts.create',
            'health.alerts.update',
            'health.alerts.delete',
            'health.supervisor.generate',
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
