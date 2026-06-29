<?php

namespace Modules\Sitemap\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SitemapPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'sitemap.view' => 'Ver el sitemap generado',
            'sitemap.generate' => 'Generar el sitemap manualmente',
            'sitemap.ping' => 'Enviar ping a motores de busqueda',
            'sitemap.settings' => 'Gestionar configuracion del sitemap',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }

        $this->command->info('Permisos del modulo Sitemap creados correctamente.');
    }
}
