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
            'helpdeskprestashop.view',
            'helpdeskprestashop.refresh',
            'helpdeskprestashop.orders.view',
            'helpdeskprestashop.orders.return',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
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
