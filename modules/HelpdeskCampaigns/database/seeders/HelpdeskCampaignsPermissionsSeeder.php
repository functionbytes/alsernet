<?php

namespace Modules\HelpdeskCampaigns\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskCampaignsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['helpdesk.campaigns.view', 'Ver campañas de helpdesk'],
            ['helpdesk.campaigns.create', 'Crear campañas de helpdesk'],
            ['helpdesk.campaigns.update', 'Actualizar campañas de helpdesk'],
            ['helpdesk.campaigns.delete', 'Eliminar campañas de helpdesk'],
            ['helpdesk.campaigns.manage', 'Gestionar campañas de helpdesk completamente'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }
    }
}
