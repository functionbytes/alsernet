<?php

namespace Modules\Forms\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FormsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->assignPermissionsToRoles();
    }

    private function createPermissions(): void
    {
        $permissions = config('forms.permissions', []);

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
                Permission::where('name', 'like', 'Forms.%')->get()
            );
        }

        $admin = Role::findByName('settings', 'web');
        if ($admin) {
            $admin->givePermissionTo(
                Permission::where('name', 'like', 'Forms.%')
                    ->where('name', '!=', 'Forms.settings.manage')
                    ->get()
            );
        }

        $manager = Role::findByName('manager', 'web');
        if ($manager) {
            $manager->givePermissionTo([
                'Forms.forms.index',
                'Forms.forms.create',
                'Forms.forms.edit',
                'Forms.submissions.index',
                'Forms.submissions.export',
                'Forms.categories.manage',
                'Forms.analytics.index',
            ]);
        }
    }
}
