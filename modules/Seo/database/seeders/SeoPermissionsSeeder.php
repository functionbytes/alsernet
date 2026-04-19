<?php

namespace Modules\Seo\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SeoPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

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

            // llms.txt (AI crawlers)
            'Seo.llms.index' => 'Ver llms.txt',
            'Seo.llms.update' => 'Editar llms.txt',

            // IndexNow (Bing/Yandex instant indexing)
            'Seo.indexnow.index' => 'Ver estado de IndexNow',
            'Seo.indexnow.submit' => 'Enviar URLs a IndexNow',

            // Static URLs
            'Seo.static-urls.index' => 'Ver URLs estáticas del sitemap',
            'Seo.static-urls.create' => 'Crear URLs estáticas del sitemap',
            'Seo.static-urls.update' => 'Actualizar URLs estáticas del sitemap',
            'Seo.static-urls.delete' => 'Eliminar URLs estáticas del sitemap',

            // Dashboard
            'Seo.dashboard.view' => 'Ver dashboard SEO',

            // Reports
            'Seo.report.view' => 'Ver reporte SEO',

            // Orphan pages (content without SEO)
            'Seo.orphans.view' => 'Ver contenido sin SEO',
            'Seo.orphans.generate' => 'Generar listado de contenido sin SEO',

            // 404 Logs
            'Seo.404-logs.view' => 'Ver errores 404',
            'Seo.404-logs.delete' => 'Eliminar errores 404',

            // SEO Templates
            'Seo.templates.index' => 'Ver plantillas SEO',
            'Seo.templates.create' => 'Crear plantillas SEO',
            'Seo.templates.update' => 'Actualizar plantillas SEO',
            'Seo.templates.delete' => 'Eliminar plantillas SEO',

            // SEO Audit
            'Seo.audit.history' => 'Ver historial de auditorías SEO',
        ];

        foreach ($permissions as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        $adminRoles = (array) config('seohelper.admin_roles', ['super-settings', 'settings']);

        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
            }
        }

        $this->command->info('Permisos del módulo Seo creados y asignados correctamente.');
    }
}
