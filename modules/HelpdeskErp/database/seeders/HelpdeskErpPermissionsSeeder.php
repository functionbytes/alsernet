<?php

namespace Modules\HelpdeskErp\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskErpPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'helpdeskerp.view' => 'Ver contexto ERP en tickets y pedidos',
            'helpdeskerp.refresh' => 'Actualizar datos ERP del cliente',
            'helpdeskerp.health.view' => 'Ver estado de salud de la integración ERP',
            'helpdeskerp.orders.detail.view' => 'Ver detalle de pedidos del ERP',
            // Consulta de NO-clientes (prospectos) en el ERP: expone datos reales
            // (balance/crédito/pedidos) de cualquier email/id, así que se reserva
            // a roles de confianza (admins) y NO se da al rol de agente.
            'helpdeskerp.prospect.view' => 'Ver datos de prospectos (no clientes) en el ERP',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo(array_keys($permissions));
        }

        // Los agentes de helpdesk necesitan ver el contexto ERP en los tickets y pedidos
        $agentViewPermissions = ['helpdeskerp.view', 'helpdeskerp.orders.detail.view'];
        $agentRole = Role::where('name', 'helpdesk-agent')->first();

        if ($agentRole) {
            $agentRole->givePermissionTo(
                array_filter($agentViewPermissions, fn ($p) => Permission::where('name', $p)->exists())
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
