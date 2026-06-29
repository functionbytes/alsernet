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
            'helpdeskerp.view',
            'helpdeskerp.refresh',
            'helpdeskerp.health.view',
            'helpdeskerp.orders.detail.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
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
