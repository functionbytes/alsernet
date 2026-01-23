<?php

namespace Modules\Mailrelay\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MailrelayPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for Mailrelay module
        $permissions = [
            // General access
            'mailrelay.access' => 'Acceder al módulo Mailrelay',

            // Campaigns
            'mailrelay.campaigns.view' => 'Ver campañas',
            'mailrelay.campaigns.create' => 'Crear campañas',
            'mailrelay.campaigns.edit' => 'Editar campañas',
            'mailrelay.campaigns.delete' => 'Eliminar campañas',
            'mailrelay.campaigns.send' => 'Enviar campañas',

            // Subscribers
            'mailrelay.subscribers.view' => 'Ver suscriptores',
            'mailrelay.subscribers.create' => 'Crear suscriptores',
            'mailrelay.subscribers.edit' => 'Editar suscriptores',
            'mailrelay.subscribers.delete' => 'Eliminar suscriptores',
            'mailrelay.subscribers.manage' => 'Gestionar grupos de suscriptores',
            'mailrelay.subscribers.sync' => 'Sincronizar con Mailrelay',

            // Imports
            'mailrelay.imports.view' => 'Ver importaciones',
            'mailrelay.imports.create' => 'Crear importaciones',
            'mailrelay.imports.delete' => 'Eliminar importaciones',

            // Email Validation
            'mailrelay.validation.use' => 'Usar validación de emails',

            // Settings
            'mailrelay.settings.manage' => 'Gestionar configuración',
        ];

        // Create or update permissions
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['description' => $description, 'guard_name' => 'web']
            );
        }

        // Assign all permissions to Super Admin role
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(array_keys($permissions));
        }

        // Create a Marketing Manager role with specific permissions
        $marketingRole = Role::firstOrCreate(
            ['name' => 'Marketing Manager'],
            ['guard_name' => 'web']
        );

        $marketingPermissions = [
            'mailrelay.access',
            'mailrelay.campaigns.view',
            'mailrelay.campaigns.create',
            'mailrelay.campaigns.edit',
            'mailrelay.campaigns.send',
            'mailrelay.subscribers.view',
            'mailrelay.subscribers.create',
            'mailrelay.subscribers.edit',
            'mailrelay.imports.view',
            'mailrelay.imports.create',
            'mailrelay.validation.use',
        ];

        $marketingRole->syncPermissions($marketingPermissions);

        // Create a Subscriber Manager role with limited permissions
        $subscriberRole = Role::firstOrCreate(
            ['name' => 'Subscriber Manager'],
            ['guard_name' => 'web']
        );

        $subscriberPermissions = [
            'mailrelay.access',
            'mailrelay.subscribers.view',
            'mailrelay.subscribers.create',
            'mailrelay.subscribers.edit',
            'mailrelay.imports.view',
            'mailrelay.imports.create',
            'mailrelay.validation.use',
        ];

        $subscriberRole->syncPermissions($subscriberPermissions);

        $this->command->info('Mailrelay permissions created successfully!');
        $this->command->info('Created '.count($permissions).' permissions');
        $this->command->info('Marketing Manager and Subscriber Manager roles configured');
    }
}
