<?php

namespace Modules\Cookie\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CookiePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'Cookie.settings.index' => 'Ver configuración de cookies',
            'Cookie.settings.update' => 'Actualizar configuración de cookies',
        ];

        foreach ($permissions as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        // Asignar los permisos nuevos a los roles con acceso total
        foreach (['super-settings', 'settings'] as $roleName) {
            $role = Role::findByName($roleName, 'web');

            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
            }
        }

        $this->command->info('Permisos del módulo Cookie creados y asignados correctamente.');
    }
}
