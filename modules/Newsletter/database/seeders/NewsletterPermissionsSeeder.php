<?php

namespace Modules\Newsletter\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class NewsletterPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->assignPermissionsToRoles();
    }

    private function createPermissions(): void
    {
        $permissions = [
            ['name' => 'View Newsletter Subscribers', 'flag' => 'Newsletter.subscribers.index'],
            ['name' => 'Manage Newsletter Subscribers', 'flag' => 'Newsletter.subscribers.manage'],
            ['name' => 'Manage Newsletter Settings', 'flag' => 'Newsletter.settings.manage'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['flag'], 'guard_name' => 'web']
            );
        }
    }

    private function assignPermissionsToRoles(): void
    {
        $superAdmin = Role::findByName('super-settings', 'web');
        if ($superAdmin) {
            $superAdmin->givePermissionTo(
                Permission::where('name', 'like', 'Newsletter.%')->get()
            );
        }

        $admin = Role::findByName('settings', 'web');
        if ($admin) {
            $admin->givePermissionTo(
                Permission::where('name', 'like', 'Newsletter.%')
                    ->where('name', '!=', 'Newsletter.settings.manage')
                    ->get()
            );
        }
    }
}
