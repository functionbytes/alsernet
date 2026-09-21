<?php

namespace Modules\HelpdeskSocial\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskSocialPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset Spatie's permission cache so the newly created permissions are
        // visible immediately (otherwise can() checks may miss them until TTL).
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'helpdesksocial.view' => 'Ver redes sociales',
            'helpdesksocial.manage' => 'Gestionar redes sociales',
            'helpdesksocial.approver' => 'Aprobar publicaciones en redes sociales',
            'helpdesksocial.accounts.manage' => 'Gestionar cuentas de redes sociales conectadas',
            'helpdesksocial.rules.manage' => 'Gestionar reglas de asignación de redes sociales',
            'helpdesksocial.rules.view' => 'Ver reglas de asignación de redes sociales',
            'helpdesksocial.templates.manage' => 'Gestionar plantillas de respuesta de redes sociales',
            'helpdesksocial.templates.view' => 'Ver plantillas de respuesta de redes sociales',
            'helpdesksocial.analytics.view' => 'Ver analítica de redes sociales',
            'helpdesksocial.mentions.update' => 'Actualizar menciones de redes sociales',
            'helpdesksocial.competitors.manage' => 'Gestionar competidores monitorizados en redes sociales',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        // Assign to admin and super-admin roles if they exist
        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo(array_keys($permissions));
        }
    }
}
