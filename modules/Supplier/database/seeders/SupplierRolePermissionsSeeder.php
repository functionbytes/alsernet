<?php

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SupplierRolePermissionsSeeder extends Seeder
{
    /**
     * Asigna todos los permisos del módulo Supplier al rol "suppliers"
     */
    public function run(): void
    {
        // El rol "suppliers" no existe; se asignan permisos al rol "manager" que es el rol
        // estándar para usuarios con acceso completo al módulo de proveedores.
        $role = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        $permissions = [
            'modules.view.suppliers',
            'can_sync_suppliers',
            'suppliers.view',
            'suppliers.view.detail',
            'suppliers.view.products',
            'suppliers.view.categories',
            'suppliers.view.sources',
            'suppliers.edit',
            'suppliers.toggle',
            'suppliers.sources.manage',
            'suppliers.categories.manage',
            'suppliers.prompts.manage',
            'suppliers.templates.manage',
            'suppliers.resources.delete',
            'suppliers.sync.trigger',
            'suppliers.sync.erp',
            'suppliers.sync.config',
            'suppliers.sync.retry',
            'suppliers.sync.delete-failures',
            'suppliers.sync.status',
            'suppliers.sync.failures',
            'suppliers.view.content',
            'suppliers.content.publish',
            'suppliers.content.translate',
            'suppliers.content.manage',
            'suppliers.view.automation',
            'suppliers.automation.run',
            'suppliers.automation.manage',
            'suppliers.monitoring.view',
            'suppliers.monitoring.manage',
            'suppliers.ai.premium',
            'suppliers.configure',
            'suppliers.import',
            'suppliers.route.operational',
            'suppliers.route.settings',
        ];

        $role->givePermissionTo($permissions);

        $this->command->info('Permisos asignados al rol "manager" exitosamente.');
        $this->command->info("Total de permisos: {$role->permissions()->count()}");
    }
}
