<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class SupplierPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // module access
            ['name' => 'modules.view.suppliers', 'description' => 'Ver módulo de proveedores en el menú'],
            ['name' => 'can_sync_suppliers', 'description' => 'Ejecutar sincronización de proveedores (gate interno)'],

            // view (sort 1-9)
            ['name' => 'suppliers.view', 'description' => 'Ver listado de proveedores'],
            ['name' => 'suppliers.view.detail', 'description' => 'Ver detalle de proveedor'],
            ['name' => 'suppliers.view.products', 'description' => 'Ver productos del proveedor'],
            ['name' => 'suppliers.view.categories', 'description' => 'Ver categorías del proveedor'],
            ['name' => 'suppliers.view.sources', 'description' => 'Ver fuentes de datos del proveedor'],

            // management (sort 10-19)
            ['name' => 'suppliers.edit', 'description' => 'Editar información de proveedores'],
            ['name' => 'suppliers.toggle', 'description' => 'Activar/desactivar proveedores'],
            ['name' => 'suppliers.sources.manage', 'description' => 'Gestionar fuentes de datos'],
            ['name' => 'suppliers.categories.manage', 'description' => 'Gestionar categorías'],
            ['name' => 'suppliers.prompts.manage', 'description' => 'Gestionar prompts de extracción'],
            ['name' => 'suppliers.templates.manage', 'description' => 'Gestionar templates de prompts'],
            ['name' => 'suppliers.resources.delete', 'description' => 'Eliminar recursos (fuentes, categorías)'],

            // sync (sort 20-29)
            ['name' => 'suppliers.sync.trigger', 'description' => 'Disparar sincronización manual'],
            ['name' => 'suppliers.sync.erp', 'description' => 'Sincronizar proveedores desde ERP'],
            ['name' => 'suppliers.sync.config', 'description' => 'Gestionar configuración de schedules de sync'],
            ['name' => 'suppliers.sync.retry', 'description' => 'Reintentar fallos de sincronización'],
            ['name' => 'suppliers.sync.delete-failures', 'description' => 'Eliminar fallos de sincronización'],
            ['name' => 'suppliers.sync.status', 'description' => 'Ver dashboard de estado de sincronización'],
            ['name' => 'suppliers.sync.failures', 'description' => 'Ver listado de fallos de sincronización'],

            // content (sort 30-39)
            ['name' => 'suppliers.view.content', 'description' => 'Ver contenido generado'],
            ['name' => 'suppliers.content.publish', 'description' => 'Publicar contenido generado'],
            ['name' => 'suppliers.content.translate', 'description' => 'Traducir contenido generado'],
            ['name' => 'suppliers.content.manage', 'description' => 'Gestionar acciones sobre contenido'],
            ['name' => 'suppliers.content.assign-others', 'description' => 'Asignar contenido a otros usuarios'],

            // automation (sort 40-49)
            ['name' => 'suppliers.view.automation', 'description' => 'Ver automatizaciones'],
            ['name' => 'suppliers.automation.run', 'description' => 'Ejecutar workflows de automatización'],
            ['name' => 'suppliers.automation.manage', 'description' => 'Configurar automatización'],

            // monitoring (sort 48-49)
            ['name' => 'suppliers.monitoring.view', 'description' => 'Ver dashboard de consumo y costos de IA'],
            ['name' => 'suppliers.monitoring.manage', 'description' => 'Gestionar presupuestos y alertas de IA'],

            // ai cost-tier (sort 49)
            ['name' => 'suppliers.ai.premium', 'description' => 'Usar modelos premium de IA (gpt-4o, claude-3-5-sonnet)'],

            // configure (sort 50-59)
            ['name' => 'suppliers.configure', 'description' => 'Acceso a configuración del módulo (replaces role:super-admin)'],
            ['name' => 'suppliers.import', 'description' => 'Importar proveedores desde ERP'],

            // route (sort 60-69)
            ['name' => 'suppliers.route.operational', 'description' => 'Acceso a rutas operacionales /suppliers'],
            ['name' => 'suppliers.route.settings', 'description' => 'Acceso a rutas de configuración /setting/suppliers'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['description' => $permission['description']]
            );
        }

        $this->command->info('Permisos de proveedores creados exitosamente.');
    }
}
