<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Document\Entities\DocumentPermission;
use Modules\Document\Entities\DocumentValidatorGroup;

class DocumentGroupPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Asigns all document permissions to a validator group
     */
    public function run(): void
    {
        // Create route-level permissions
        $this->createRoutePermissions();

        // Get all available permissions
        $allPermissions = DocumentPermission::active()->pluck('id')->toArray();

        if (empty($allPermissions)) {
            $this->command->error('No permissions found in database. Run document permission migrations first.');

            return;
        }

        // Find or create test/default validator group
        $group = DocumentValidatorGroup::firstOrCreate(
            ['name' => 'Grupo de Prueba'],
            [
                'key' => 'test-group',
                'description' => 'Grupo con acceso completo a todos los permisos del documento',
                'is_active' => true,
            ]
        );

        // Assign all permissions to the group
        $group->permissions()->sync($allPermissions);

        $this->command->info('✓ Asignados '.count($allPermissions)." permisos al grupo: {$group->name}");

        // Display permission breakdown
        $byCategory = DocumentPermission::active()
            ->get()
            ->groupBy('category');

        $this->command->line("\nPermisos por categoría:");
        foreach ($byCategory as $category => $perms) {
            $this->command->info('  ✓ '.strtoupper($category).': '.count($perms).' permisos');
        }

        $this->command->newLine();
        $this->command->info('Para asignar permisos selectivos, ve a:');
        $this->command->line("/settings/documents/groups/permissions/{$group->id}/edit");
    }

    /**
     * Create route-level permissions for document access
     */
    private function createRoutePermissions(): void
    {
        $routePermissions = [
            // Main routes
            [
                'name' => 'route-all-documents',
                'label' => 'Ver todos los documentos',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET / (listado completo de documentos)',
                'sort_order' => 1,
            ],
            [
                'name' => 'route-show-documents',
                'label' => 'Ver documentos individuales',
                'category' => 'route',
                'description' => 'Acceso a rutas de ver documentos: show, summary',
                'sort_order' => 2,
            ],
            [
                'name' => 'route-pending-documents',
                'label' => 'Ver documentos pendientes',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /pending (documentos en etapa de validación)',
                'sort_order' => 2.5,
            ],
            [
                'name' => 'route-manage-documents',
                'label' => 'Gestionar documentos',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /manage/{uid} (gestión detallada)',
                'sort_order' => 3,
            ],
            // Sync/Import routes
            [
                'name' => 'route-sync-documents',
                'label' => 'Importar documentos',
                'category' => 'route',
                'description' => 'Acceso general a rutas de sincronización e importación',
                'sort_order' => 5,
            ],
            [
                'name' => 'route-sync-api-documents',
                'label' => 'Sincronizar desde API',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /import/api (sincronización desde PrestaShop API)',
                'sort_order' => 6,
            ],
            [
                'name' => 'route-sync-erp-documents',
                'label' => 'Sincronizar desde ERP',
                'category' => 'route',
                'description' => 'Acceso a la ruta GET /import/erp (sincronización desde ERP)',
                'sort_order' => 7,
            ],
            // Email routes
            [
                'name' => 'route-mails-documents',
                'label' => 'Ver emails de documentos',
                'category' => 'route',
                'description' => 'Acceso a la vista de emails asociados a un documento',
                'sort_order' => 8,
            ],
        ];

        foreach ($routePermissions as $permission) {
            DocumentPermission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('✓ '.count($routePermissions).' permisos de rutas sincronizados con la base de datos');
    }
}
