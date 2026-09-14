<?php

namespace Modules\Storage\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StoragePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'storage.view' => 'Ver configuración de almacenamiento',
            'storage.create' => 'Crear discos de almacenamiento',
            'storage.update' => 'Editar discos de almacenamiento',
            'storage.delete' => 'Eliminar discos de almacenamiento',
            'storage.manage' => 'Gestión completa de almacenamiento',
        ];

        foreach ($permissions as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (['super-settings', 'settings'] as $roleName) {
            $role = Role::findByName($roleName, 'web');

            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
            }
        }

        $this->command->info('Permisos del módulo Storage creados y asignados correctamente.');
    }
}
