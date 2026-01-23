<?php

namespace Modules\Mailer\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MailerPermissionsSeeder extends Seeder
{
    /**
     * Create Mailer module permissions
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all Mailer permissions
        $permissions = $this->getMailerPermissions();

        // Create permissions
        foreach ($permissions as $permission => $description) {
            Permission::findOrCreate($permission, 'web');
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    /**
     * Get all Mailer module permissions
     */
    private function getMailerPermissions(): array
    {
        return [
            // Email Templates
            'mailer.templates.view' => 'Ver plantillas de correo',
            'mailer.templates.create' => 'Crear plantillas de correo',
            'mailer.templates.update' => 'Actualizar plantillas de correo',
            'mailer.templates.delete' => 'Eliminar plantillas de correo',
            'mailer.templates.preview' => 'Previsualizar plantillas',
            'mailer.templates.manage' => 'Gestionar plantillas de correo',

            // Layout Components
            'mailer.components.view' => 'Ver componentes de correo',
            'mailer.components.create' => 'Crear componentes de correo',
            'mailer.components.update' => 'Actualizar componentes de correo',
            'mailer.components.delete' => 'Eliminar componentes de correo',
            'mailer.components.preview' => 'Previsualizar componentes',
            'mailer.components.manage' => 'Gestionar componentes de correo',

            // Email Variables
            'mailer.variables.view' => 'Ver variables de correo',
            'mailer.variables.create' => 'Crear variables de correo',
            'mailer.variables.update' => 'Actualizar variables de correo',
            'mailer.variables.delete' => 'Eliminar variables de correo',
            'mailer.variables.manage' => 'Gestionar variables de correo',

            // Email Endpoints (API)
            'mailer.endpoints.view' => 'Ver puntos finales de correo',
            'mailer.endpoints.create' => 'Crear puntos finales de correo',
            'mailer.endpoints.update' => 'Actualizar puntos finales de correo',
            'mailer.endpoints.delete' => 'Eliminar puntos finales de correo',
            'mailer.endpoints.logs' => 'Ver registros de puntos finales',
            'mailer.endpoints.manage' => 'Gestionar puntos finales de correo',
            'mailer.endpoints.regenerate-token' => 'Regenerar token de API',

            // General Mailer Management
            'mailer.manage' => 'Gestionar módulo Mailer',
        ];
    }

    /**
     * Assign Mailer permissions to roles
     */
    private function assignPermissionsToRoles(): void
    {
        // Super Admin - Full access
        $superAdminRole = Role::findOrCreate('super-admin', 'web');
        $superAdminRole->givePermissionTo(Permission::where('name', 'like', 'mailer.%')->get());

        // Admin - Full access
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo(Permission::where('name', 'like', 'mailer.%')->get());

        // Manager - Can view and create, but not delete
        $managerRole = Role::findOrCreate('manager', 'web');
        $managerRole->givePermissionTo([
            'mailer.templates.view',
            'mailer.templates.create',
            'mailer.templates.update',
            'mailer.templates.preview',
            'mailer.components.view',
            'mailer.components.create',
            'mailer.components.update',
            'mailer.components.preview',
            'mailer.variables.view',
            'mailer.variables.create',
            'mailer.variables.update',
            'mailer.endpoints.view',
            'mailer.endpoints.create',
            'mailer.endpoints.update',
            'mailer.endpoints.logs',
        ]);

        // Administrative - View only
        $administrativeRole = Role::findOrCreate('administrative', 'web');
        $administrativeRole->givePermissionTo([
            'mailer.templates.view',
            'mailer.templates.preview',
            'mailer.components.view',
            'mailer.components.preview',
            'mailer.variables.view',
            'mailer.endpoints.view',
            'mailer.endpoints.logs',
        ]);
    }
}
