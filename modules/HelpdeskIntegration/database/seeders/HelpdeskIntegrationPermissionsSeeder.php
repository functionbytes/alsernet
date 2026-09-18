<?php

namespace Modules\HelpdeskIntegration\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskIntegrationPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'helpdeskintegration.providers.view',
            'helpdeskintegration.providers.create',
            'helpdeskintegration.providers.update',
            'helpdeskintegration.providers.delete',
            'helpdeskintegration.providers.manage',

            // Vincular/desvincular integraciones de un cliente desde el inbox.
            // Antes bastaba con poder editar el cliente (helpdesk.customers.update);
            // este permiso dedicado permite retirar la gestión de integraciones
            // sin quitar la edición de clientes.
            'helpdesk.integrations.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        $this->backfillIntegrationsManage();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * `helpdesk.integrations.manage` es nuevo: para no dejar sin acceso a
     * quien ya vinculaba/desvinculaba integraciones (cualquier rol con
     * `helpdesk.customers.update`, el requisito anterior), se otorga a esos
     * mismos roles. Idempotente y seguro de re-ejecutar.
     */
    private function backfillIntegrationsManage(): void
    {
        Role::whereHas('permissions', fn ($q) => $q->where('name', 'helpdesk.customers.update'))
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo('helpdesk.integrations.manage'));
    }
}
