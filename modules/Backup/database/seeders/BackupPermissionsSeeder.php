<?php

namespace Modules\Backup\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BackupPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'Backup.backups.index' => 'Ver backups',
            'Backup.backups.download' => 'Descargar backups',
            'Backup.backups.delete' => 'Eliminar backups',
            'Backup.schedules.index' => 'Ver programaciones de backup',
            'Backup.schedules.create' => 'Crear programaciones de backup',
            'Backup.schedules.update' => 'Actualizar programaciones de backup',
            'Backup.schedules.delete' => 'Eliminar programaciones de backup',
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

        $this->command->info('Permisos del módulo Backup creados y asignados correctamente.');
    }
}
