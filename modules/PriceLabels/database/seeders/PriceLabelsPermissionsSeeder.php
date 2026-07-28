<?php

namespace Modules\PriceLabels\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
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
            ['modules.view.pricelabels', 'Ver modulo Etiquetas de precio en el nav'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
    }
}
