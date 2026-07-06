<?php

namespace Modules\HelpdeskAgents\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskAgentsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['helpdesk.aiagents.view', 'Ver agentes IA'],
            ['helpdesk.aiagents.create', 'Crear agentes IA'],
            ['helpdesk.aiagents.update', 'Actualizar agentes IA'],
            ['helpdesk.aiagents.delete', 'Eliminar agentes IA'],
            ['helpdesk.aiagents.manage', 'Gestionar agentes IA completamente'],

            // Turnos/vacaciones/guardias (ScheduleController) — el gate real
            // `can:helpdesk.schedule.*` y las policies AgentShift/OncallRotation
            // consultaban estos permisos, que nunca se sembraban.
            ['helpdesk.schedule.view', 'Ver turnos y guardias'],
            ['helpdesk.schedule.create', 'Crear turnos y guardias'],
            ['helpdesk.schedule.update', 'Actualizar turnos y guardias'],
            ['helpdesk.schedule.delete', 'Eliminar turnos y guardias'],
            ['helpdesk.schedule.manage', 'Gestionar turnos y guardias completamente'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
    }
}
