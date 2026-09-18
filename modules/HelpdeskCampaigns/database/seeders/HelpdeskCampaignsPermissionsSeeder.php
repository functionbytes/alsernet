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
            ['helpdesk.campaigns.view', 'Ver campanas de helpdesk'],
            ['helpdesk.campaigns.create', 'Crear campanas de helpdesk'],
            ['helpdesk.campaigns.update', 'Actualizar campanas de helpdesk'],
            ['helpdesk.campaigns.delete', 'Eliminar campanas de helpdesk'],
            ['helpdesk.campaigns.manage', 'Gestionar campanas de helpdesk completamente'],
        ];

        foreach ($permissions as [$name, $description]) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description]
            );
        }
    }
}
