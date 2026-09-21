<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AuthPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'auth.impersonate' => 'Suplantar usuarios',
            'auth.settings.view' => 'Ver configuración de autenticación',
            'auth.settings.update' => 'Actualizar configuración de autenticación',
            'auth.audit.view' => 'Ver auditoría de autenticación',
            'auth.devices.manage' => 'Gestionar dispositivos de confianza',
            'auth.api-tokens.manage' => 'Gestionar tokens de API personales',
            // Usado por SessionPolicy (viewAny/view/update/delete/manage); nunca
            // se había sembrado, por lo que la gestión de sesiones daba 403
            // permanente salvo para el dueño de la propia sesión.
            'auth.sessions.view' => 'Ver y gestionar sesiones de otros usuarios',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
