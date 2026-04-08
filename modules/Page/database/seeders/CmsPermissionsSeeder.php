<?php

namespace Modules\Page\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CmsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->createRoles();
        $this->assignPermissionsToRoles();
    }

    /**
     * Create CMS permissions
     */
    protected function createPermissions(): void
    {
        $permissions = [
            // Page Module Permissions
            'page.view' => 'Ver páginas',
            'page.view-all' => 'Ver todas las páginas',
            'page.create' => 'Crear páginas',
            'page.update' => 'Editar páginas',
            'page.delete' => 'Eliminar páginas',
            'page.publish' => 'Publicar páginas',
            'page.manage' => 'Gestionar páginas',
            'page.approve' => 'Aprobar o rechazar páginas en revisión',
            'page.manage-categories' => 'Gestionar categorías de páginas',
            'page.manage-webhooks' => 'Gestionar webhooks de páginas',
            'page.bulk-action' => 'Ejecutar acciones masivas en páginas',
            'page.import' => 'Importar páginas',
            'page.export' => 'Exportar páginas',
            'page.view-analytics' => 'Ver analíticas de páginas',
            'page.manage-performance' => 'Gestionar análisis de rendimiento PageSpeed',

            // Menu Module Permissions
            'menu.view' => 'Ver menús',
            'menu.create' => 'Crear menús',
            'menu.update' => 'Editar menús',
            'menu.delete' => 'Eliminar menús',
            'menu.manage' => 'Gestionar menús',
            'menu.manage-items' => 'Gestionar elementos de menú',

            // SEO Helper Permissions
            'seo.view' => 'Ver configuración SEO',
            'seo.manage' => 'Gestionar SEO',
            'seo.edit-meta' => 'Editar meta tags',
            'seo.manage-redirects' => 'Gestionar redirecciones',

            // Shortcode Permissions
            'shortcode.use' => 'Usar shortcodes',
            'shortcode.manage' => 'Gestionar shortcodes',
            'shortcode.create-custom' => 'Crear shortcodes personalizados',

            // Sitemap Permissions
            'sitemap.view' => 'Ver sitemap',
            'sitemap.generate' => 'Generar sitemap',
            'sitemap.manage' => 'Gestionar sitemap',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('✓ CMS Permissions created: '.count($permissions));
    }

    /**
     * Create CMS roles
     */
    protected function createRoles(): void
    {
        $roles = [
            'cms-settings' => 'Administrador CMS',
            'cms-editor' => 'Editor CMS',
            'cms-author' => 'Autor CMS',
            'cms-viewer' => 'Visualizador CMS',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('✓ CMS Roles created: '.count($roles));
    }

    /**
     * Assign permissions to roles
     */
    protected function assignPermissionsToRoles(): void
    {
        // Super Admin - All permissions
        if (Role::where('name', 'super-settings')->exists()) {
            $superAdmin = Role::findByName('super-settings');
            $superAdmin->givePermissionTo(Permission::all());
            $this->command->info('✓ Super Admin: All CMS permissions assigned');
        }

        // CMS Admin - Full CMS access
        $cmsAdmin = Role::findByName('cms-settings');
        $cmsAdmin->givePermissionTo([
            // Pages
            'page.view',
            'page.view-all',
            'page.create',
            'page.update',
            'page.delete',
            'page.publish',
            'page.manage',
            'page.approve',
            'page.manage-categories',
            'page.manage-webhooks',
            'page.bulk-action',
            'page.import',
            'page.export',
            'page.view-analytics',
            'page.manage-performance',
            // Menus
            'menu.view',
            'menu.create',
            'menu.update',
            'menu.delete',
            'menu.manage',
            'menu.manage-items',
            // SEO
            'seo.view',
            'seo.manage',
            'seo.edit-meta',
            'seo.manage-redirects',
            // Shortcodes
            'shortcode.use',
            'shortcode.manage',
            'shortcode.create-custom',
            // Sitemap
            'sitemap.view',
            'sitemap.generate',
            'sitemap.manage',
        ]);
        $this->command->info('✓ CMS Admin: All CMS permissions assigned');

        // CMS Editor - Can edit content but not manage structure
        $cmsEditor = Role::findByName('cms-editor');
        $cmsEditor->givePermissionTo([
            'page.view',
            'page.view-all',
            'page.create',
            'page.update',
            'page.publish',
            'page.bulk-action',
            'page.export',
            'page.view-analytics',
            'menu.view',
            'menu.update',
            'menu.manage-items',
            'seo.view',
            'seo.edit-meta',
            'shortcode.use',
            'sitemap.view',
        ]);
        $this->command->info('✓ CMS Editor: Editor permissions assigned');

        // CMS Author - Can create and edit own content
        $cmsAuthor = Role::findByName('cms-author');
        $cmsAuthor->givePermissionTo([
            'page.view',
            'page.create',
            'page.update',
            'seo.view',
            'seo.edit-meta',
            'shortcode.use',
        ]);
        $this->command->info('✓ CMS Author: Author permissions assigned');

        // CMS Viewer - Read-only access
        $cmsViewer = Role::findByName('cms-viewer');
        $cmsViewer->givePermissionTo([
            'page.view',
            'menu.view',
            'seo.view',
            'sitemap.view',
        ]);
        $this->command->info('✓ CMS Viewer: Viewer permissions assigned');
    }
}
