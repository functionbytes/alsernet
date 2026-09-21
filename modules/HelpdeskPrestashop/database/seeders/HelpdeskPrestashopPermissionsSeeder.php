<?php

namespace Modules\HelpdeskPrestashop\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskPrestashopPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->renameLegacyPermissions();

        $permissions = [
            'helpdeskprestashop.view' => 'Ver contexto PrestaShop en el inbox',
            'helpdeskprestashop.refresh' => 'Actualizar datos de PrestaShop del cliente',
            'helpdeskprestashop.orders.view' => 'Ver pedidos de PrestaShop',
            'helpdeskprestashop.orders.return' => 'Gestionar devoluciones de pedidos de PrestaShop',
            // Acciones que MUTAN el pedido en PrestaShop (cambiar estado,
            // asignar seguimiento) desde el workspace del inbox.
            'helpdeskprestashop.orders.manage' => 'Modificar pedidos de PrestaShop (estado, seguimiento)',
            // Acciones que MUTAN el carrito EN VIVO del cliente en PrestaShop
            // (dirección, productos, cantidades, cupón) — permiso propio y
            // separado de orders.manage: tocar un carrito activo es más
            // sensible que un pedido ya cerrado, así que no se hereda de él.
            'helpdeskprestashop.carts.manage' => 'Modificar el carrito en vivo del cliente en PrestaShop',
            // Consulta de NO-clientes (prospectos) en PrestaShop: expone datos
            // reales (pedidos, carrito) de cualquier email sin Customer local
            // asociado, así que se reserva a roles de confianza (admins) y NO
            // se da al rol de agente — mismo criterio que helpdeskerp.prospect.view.
            'helpdeskprestashop.prospect.view' => 'Ver datos de prospectos (no clientes) en PrestaShop',
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

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Rename permissions that used the old 4-segment naming so role
     * assignments survive the convention update.
     */
    private function renameLegacyPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $renames = [
            'helpdeskprestashop.orders.detail.view' => 'helpdeskprestashop.orders.view',
        ];

        foreach ($renames as $old => $new) {
            $legacy = Permission::where('name', $old)->where('guard_name', 'web')->first();

            if ($legacy === null) {
                continue;
            }

            $target = Permission::where('name', $new)->where('guard_name', 'web')->first();

            if ($target === null) {
                $legacy->update(['name' => $new]);

                continue;
            }

            // Both exist: migrate role assignments to the new one and drop the legacy.
            foreach ($legacy->roles as $role) {
                $role->givePermissionTo($target);
            }

            $legacy->delete();
        }
    }
}
