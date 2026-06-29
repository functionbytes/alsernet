<?php

namespace Modules\HelpdeskSocial\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HelpdeskSocialPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'helpdesksocial.view',
            'helpdesksocial.manage',
            'helpdesksocial.approver',
            'helpdesksocial.accounts.manage',
            'helpdesksocial.rules.manage',
            'helpdesksocial.rules.view',
            'helpdesksocial.templates.manage',
            'helpdesksocial.templates.view',
            'helpdesksocial.analytics.view',
            'helpdesksocial.mentions.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign to admin and super-admin roles if they exist
        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }
    }
}
