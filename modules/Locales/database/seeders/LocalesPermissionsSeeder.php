<?php

namespace Modules\Locales\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class LocalesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'locale.view',
            'locale.create',
            'locale.update',
            'locale.delete',
            'locale.set-default',
            'locale.toggle',
            'locale.translate',
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
