<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskTicketsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['name' => 'helpdesk.tickets.view',    'description' => 'Ver tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.create',   'description' => 'Crear tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.update',   'description' => 'Actualizar tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.delete',   'description' => 'Eliminar tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.manage',   'description' => 'Gestionar completamente los tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.assign',   'description' => 'Asignar tickets a agentes'],
            ['name' => 'helpdesk.tickets.close',    'description' => 'Cerrar tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.resolve',  'description' => 'Resolver tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.merge',    'description' => 'Fusionar tickets del helpdesk'],
            ['name' => 'helpdesk.tickets.settings', 'description' => 'Gestionar configuracion de tickets (categorias, SLA, estados)'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['description' => $permission['description']],
            );

            $this->command->info("Permiso creado: {$permission['name']}");
        }
    }
}
