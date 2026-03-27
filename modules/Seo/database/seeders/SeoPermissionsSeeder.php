<?php

namespace Modules\Seo\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // SEO Metas
            'Seo.metas.index' => 'Ver meta SEO',
            'Seo.metas.create' => 'Crear meta SEO',
            'Seo.metas.update' => 'Actualizar meta SEO',
            'Seo.metas.delete' => 'Eliminar meta SEO',

            // Redirects (already in use by SeoRedirectController)
            'Seo.redirects.index' => 'Ver redirecciones SEO',
            'Seo.redirects.create' => 'Crear redirecciones SEO',
            'Seo.redirects.update' => 'Actualizar redirecciones SEO',
            'Seo.redirects.delete' => 'Eliminar redirecciones SEO',

            // Robots.txt
            'Seo.robots.index' => 'Ver robots.txt',
            'Seo.robots.update' => 'Editar robots.txt',

            // Static URLs
            'Seo.static-urls.index' => 'Ver URLs estáticas del sitemap',
            'Seo.static-urls.create' => 'Crear URLs estáticas del sitemap',
            'Seo.static-urls.update' => 'Actualizar URLs estáticas del sitemap',
            'Seo.static-urls.delete' => 'Eliminar URLs estáticas del sitemap',
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

        $this->command->info('Permisos del módulo Seo creados y asignados correctamente.');
    }
}
