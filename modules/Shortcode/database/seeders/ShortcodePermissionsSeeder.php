<?php

namespace Modules\Shortcode\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShortcodePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            [
                'name' => 'shortcode.view',
                'description' => 'Ver listado y referencia de shortcodes',
            ],
            [
                'name' => 'shortcode.manage',
                'description' => 'Administrar shortcodes (limpiar cache, ejecutar acciones)',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ]);

            $this->command->info("Permiso creado: {$permission['name']}");
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();

        if ($adminRole) {
            $adminRole->givePermissionTo([
                'shortcode.view',
                'shortcode.manage',
            ]);
            $this->command->info('Permisos de shortcode asignados al rol: admin');
        }
    }
}
