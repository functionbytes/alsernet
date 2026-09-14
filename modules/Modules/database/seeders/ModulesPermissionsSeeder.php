<?php

namespace Modules\Modules\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ModulesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'modules.manage', 'guard_name' => 'web']);

        $superSettings = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $superSettings->givePermissionTo('modules.manage');

        $this->command->info('Permisos del módulo Modules creados correctamente.');
    }
}
