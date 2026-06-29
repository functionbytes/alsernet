<?php

namespace Modules\HelpdeskEmailLog\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskEmailLogPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'helpdeskemaillog.view',
            'helpdeskemaillog.manage',
            'helpdeskemaillog.settings.view',
            'helpdeskemaillog.settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
    }
}
