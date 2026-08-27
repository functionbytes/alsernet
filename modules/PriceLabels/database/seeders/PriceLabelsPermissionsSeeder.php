<?php

namespace Modules\PriceLabels\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PriceLabelsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['pricelabels.view', 'Ver etiquetas de precio'],
            ['pricelabels.create', 'Crear etiquetas de precio'],
            ['pricelabels.update', 'Actualizar etiquetas de precio'],
            ['pricelabels.delete', 'Eliminar etiquetas de precio'],
            ['pricelabels.manage', 'Gestionar etiquetas de precio completamente'],
            ['pricelabels.settings.view', 'Ver configuracion de etiquetas de precio'],
            ['pricelabels.settings.update', 'Actualizar configuracion de etiquetas de precio'],
            ['modules.view.pricelabels', 'Ver modulo Etiquetas de precio en el nav'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        // Sin esto el modulo queda invisible incluso para el super-admin: aqui
        // los permisos no se conceden por un Gate::before, hay que asignarlos
        // al rol. Mismo criterio que el seeder de GiftMessage.
        $permissionNames = array_column($permissions, 0);

        $adminRoles = Role::whereIn('name', ['super-admin', 'super-settings'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissionNames);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
