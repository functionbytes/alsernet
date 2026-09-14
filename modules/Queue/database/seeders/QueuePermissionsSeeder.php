<?php

namespace Modules\Queue\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class QueuePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'queue.view' => 'Ver monitor de colas',
            'queue.manage' => 'Gestionar colas (reintentar, eliminar trabajos)',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['super-settings', 'settings'] as $roleName) {
            $role = Role::findByName($roleName, 'web');

            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
            }
        }

        $this->command->info('Permisos del módulo Queue creados y asignados correctamente.');
    }
}
