<?php

namespace Modules\Remarketing\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RemarketingPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // General
            'remarketing.view' => 'Ver módulo Remarketing',
            'remarketing.manage' => 'Gestionar módulo Remarketing',

            // Settings
            'remarketing.settings.view' => 'Ver configuración de Remarketing',
            'remarketing.settings.update' => 'Editar configuración de Remarketing',

            // Stores
            'remarketing.stores.view' => 'Ver tiendas',
            'remarketing.stores.create' => 'Crear tiendas',
            'remarketing.stores.update' => 'Actualizar tiendas',
            'remarketing.stores.delete' => 'Eliminar tiendas',

            // Customers
            'remarketing.customers.view' => 'Ver clientes',
            'remarketing.customers.create' => 'Crear clientes',
            'remarketing.customers.update' => 'Actualizar clientes',
            'remarketing.customers.delete' => 'Eliminar clientes',
            'remarketing.customers.export' => 'Exportar clientes',

            // Products
            'remarketing.products.view' => 'Ver productos',

            // Segments
            'remarketing.segments.view' => 'Ver segmentos',
            'remarketing.segments.create' => 'Crear segmentos',
            'remarketing.segments.update' => 'Actualizar segmentos',
            'remarketing.segments.delete' => 'Eliminar segmentos',

            // Campaigns
            'remarketing.campaigns.view' => 'Ver campañas',
            'remarketing.campaigns.create' => 'Crear campañas',
            'remarketing.campaigns.update' => 'Actualizar campañas',
            'remarketing.campaigns.delete' => 'Eliminar campañas',
            'remarketing.campaigns.send' => 'Enviar campañas',

            // Automations
            'remarketing.automations.view' => 'Ver automatizaciones',
            'remarketing.automations.create' => 'Crear automatizaciones',
            'remarketing.automations.update' => 'Actualizar automatizaciones',
            'remarketing.automations.delete' => 'Eliminar automatizaciones',

            // Templates
            'remarketing.templates.view' => 'Ver plantillas',
            'remarketing.templates.create' => 'Crear plantillas',
            'remarketing.templates.update' => 'Actualizar plantillas',
            'remarketing.templates.delete' => 'Eliminar plantillas',

            // Reports
            'remarketing.reports.view' => 'Ver reportes',

            // Suppressions
            'remarketing.suppressions.view' => 'Ver supresiones',
            'remarketing.suppressions.manage' => 'Gestionar supresiones',

            // DSR (Data Subject Requests)
            'remarketing.dsr.export' => 'Exportar datos de solicitudes DSR',
            'remarketing.dsr.delete' => 'Eliminar datos de solicitudes DSR',

            // Automation triggers log
            'remarketing.triggers.view' => 'Ver log de triggers de automation',
        ];

        foreach ($permissions as $name => $description) {
            Permission::findOrCreate($name, 'web');
        }

        $adminRoles = ['super-settings', 'settings'];

        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
            }
        }

        $this->command->info('Permisos del módulo Remarketing creados y asignados correctamente.');
    }
}
