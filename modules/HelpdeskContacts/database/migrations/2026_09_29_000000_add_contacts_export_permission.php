<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * 29-sep-2026 (auditoría de seguridad): la exportación CSV de contactos (hasta
 * 5.000 filas con PII) solo exigía 'contacts.view', que tiene cualquier agente.
 * Se crea 'contacts.export' y se asigna a los roles de administración/gestión
 * que ya pueden ver contactos. Los agentes (helpdesk-agent) dejan de poder
 * exportar; un admin puede dárselo desde Roles si hace falta.
 *
 * Aditiva e idempotente. down() solo borra el permiso nuevo.
 */
return new class extends Migration
{
    private const ROLES = ['super-admin', 'super-settings', 'settings', 'admin', 'helpdesk-admin', 'helpdesk-manager'];

    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::findOrCreate('contacts.export', 'web');

        if (Schema::hasColumn($permission->getTable(), 'description')) {
            $permission->forceFill(['description' => 'Exportar contactos (CSV)'])->save();
        }

        foreach (Role::whereIn('name', self::ROLES)->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo($permission);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'contacts.export')->where('guard_name', 'web')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
