<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * 29-sep-2026 (auditoría B3): las rutas /panel/settings/prestashop/* solo
 * tenían el middleware 'settings', que no comprueba nada; cualquier usuario del
 * panel veía la contraseña de la BD de la tienda. Se crea un permiso propio y se
 * da a los roles que ya administran los ajustes (los mismos que tienen
 * 'modules.view.prestashop'). Aditiva: no quita nada a nadie.
 */
return new class extends Migration
{
    private const PERMISSION = 'prestashop.settings.manage';

    private const ROLES = ['super-admin', 'super-settings', 'settings'];

    public function up(): void
    {
        DB::transaction(function () {
            $permission = Permission::findOrCreate(self::PERMISSION, 'web');

            if (DB::getSchemaBuilder()->hasColumn('permissions', 'description')) {
                DB::table('permissions')
                    ->where('id', $permission->id)
                    ->update(['description' => 'Gestionar la configuración de PrestaShop (credenciales, conexión y bloqueos)']);
            }

            Role::query()
                ->whereIn('name', self::ROLES)
                ->where('guard_name', 'web')
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()->where('name', self::PERMISSION)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
