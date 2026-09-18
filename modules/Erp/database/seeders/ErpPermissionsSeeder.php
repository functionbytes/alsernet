<?php

namespace Modules\Erp\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ErpPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Gestión de los ERP Endpoints salientes (crear/editar/ejecutar/tokens).
        // Antes no existía y las rutas solo pedían auth → cualquier usuario
        // autenticado podía configurar y ejecutar peticiones salientes (SSRF).
        Permission::firstOrCreate(['name' => 'erp.endpoints.manage', 'guard_name' => 'web']);

        // Superficie ERP de consulta (customer/products/suppliers/families/subfamilies/groups).
        // Estos permisos quedan listos pero inertes mientras `erp.api-auth` siga
        // deshabilitado (config('erp.api.enabled') = false): el middleware `can:`
        // nunca llega a evaluarse porque la ruta ya responde sin exigir auth.
        Permission::firstOrCreate(['name' => 'erp.customer.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erp.customer.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erp.products.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erp.suppliers.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'erp.jerarquia.view', 'guard_name' => 'web']);

        $roles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        $roles->each(fn (Role $role) => $role->givePermissionTo([
            'erp.endpoints.manage',
            'erp.customer.view',
            'erp.customer.update',
            'erp.products.view',
            'erp.suppliers.view',
            'erp.jerarquia.view',
        ]));

        // Rol de minimo privilegio para el usuario de servicio (bridge Sanctum,
        // ver IssueBridgeTokenCommand): solo puede buscar/consultar clientes,
        // nunca actualizarlos ni gestionar endpoints salientes.
        Role::firstOrCreate(['name' => 'erp-service', 'guard_name' => 'web'])
            ->givePermissionTo(['erp.customer.view']);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
