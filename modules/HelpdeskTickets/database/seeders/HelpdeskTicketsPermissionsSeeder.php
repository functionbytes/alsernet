<?php

namespace Modules\HelpdeskTickets\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
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
            ['name' => 'helpdesk.tickets.emails.view',   'description' => 'Ver la bandeja de emails enviados del helpdesk'],
            ['name' => 'helpdesk.tickets.emails.send',   'description' => 'Redactar y enviar emails desde el helpdesk'],
            ['name' => 'helpdesk.tickets.emails.resend', 'description' => 'Reenviar emails del helpdesk'],
            ['name' => 'helpdesk.tickets.emails.delete', 'description' => 'Eliminar emails de la bandeja del helpdesk'],
            ['name' => 'helpdesk.tickets.emails.send_to_any', 'description' => 'Enviar/reenviar emails del helpdesk a un destinatario distinto del cliente del ticket'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['description' => $permission['description']],
            );

            $this->command->info("Permiso creado: {$permission['name']}");
        }

        // Sin esto el modulo queda invisible incluso para el super-admin: aqui
        // los permisos no se conceden por un Gate::before, hay que asignarlos
        // al rol. Mismo criterio que el seeder de PriceLabels/GiftMessage.
        $permissionNames = array_column($permissions, 'name');

        $adminRoles = Role::whereIn('name', ['super-admin', 'super-settings'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissionNames);
        }

        // Hasta el 8-sep-2026 este seeder SOLO daba permisos a los roles de
        // administrador: helpdesk-agent/helpdesk-manager se quedaban con
        // cero permisos de tickets, así que aunque el gate de rol de la ruta
        // (ver HelpdeskTicketsServiceProvider::loadManagerRoutes()) dejara
        // pasar a un agente real, TicketPolicy le denegaba todo de todas
        // formas — el sistema de permisos fino no gobernaba nada en la
        // práctica.
        //
        // El trabajo del día a día de un agente: ver/crear/actualizar
        // tickets, asignarlos (incluido asignárselos a sí mismo), cerrarlos y
        // resolverlos, y mandar/leer los correos del hilo. NO incluye
        // eliminar, fusionar ni la configuración del módulo (categorías,
        // SLA, estados) — eso sigue siendo cosa de un manager o admin.
        $agentPermissions = [
            'helpdesk.tickets.view',
            'helpdesk.tickets.create',
            'helpdesk.tickets.update',
            'helpdesk.tickets.assign',
            'helpdesk.tickets.close',
            'helpdesk.tickets.resolve',
            'helpdesk.tickets.emails.view',
            'helpdesk.tickets.emails.send',
        ];

        if ($agentRole = Role::where('name', 'helpdesk-agent')->first()) {
            $agentRole->givePermissionTo($agentPermissions);
        }

        // Un manager hace todo lo del agente sobre CUALQUIER equipo
        // (helpdesk.tickets.manage — ver TicketPolicy::inScope()), y además
        // fusiona/elimina tickets y reenvía o borra correos del hilo. La
        // configuración del módulo (helpdesk.tickets.settings) sigue sin
        // dársele: eso es de administración, no de gestión del día a día.
        $managerPermissions = array_merge($agentPermissions, [
            'helpdesk.tickets.manage',
            'helpdesk.tickets.delete',
            'helpdesk.tickets.merge',
            'helpdesk.tickets.emails.resend',
            'helpdesk.tickets.emails.delete',
            'helpdesk.tickets.emails.send_to_any',
        ]);

        if ($managerRole = Role::where('name', 'helpdesk-manager')->first()) {
            $managerRole->givePermissionTo($managerPermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
