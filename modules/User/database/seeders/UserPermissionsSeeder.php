<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view-users' => 'Ver usuarios',
            'create-users' => 'Crear usuarios',
            'edit-users' => 'Editar usuarios',
            'delete-users' => 'Eliminar usuarios',
            'impersonate-users' => 'Suplantar usuarios',
            'manage-users' => 'Gestionar usuarios',
            'bulk-action-users' => 'Acciones masivas sobre usuarios',
            'export-users' => 'Exportar usuarios',
        ];

        foreach ($permissions as $permission => $description) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo(array_keys($permissions));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
