<?php

namespace Modules\Ads\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AdsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['name' => 'ads.view', 'description' => 'Ver anuncios', 'guard_name' => 'web'],
            ['name' => 'ads.create', 'description' => 'Crear anuncios', 'guard_name' => 'web'],
            ['name' => 'ads.update', 'description' => 'Editar anuncios', 'guard_name' => 'web'],
            ['name' => 'ads.delete', 'description' => 'Eliminar anuncios', 'guard_name' => 'web'],
            ['name' => 'ads.settings', 'description' => 'Configurar anuncios', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                ['description' => $permission['description']]
            );
        }
    }
}
