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
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
    }
}
