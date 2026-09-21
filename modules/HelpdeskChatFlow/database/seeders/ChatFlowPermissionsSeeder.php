<?php

namespace Modules\HelpdeskChatFlow\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class ChatFlowPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'chatflow.view' => 'Ver flujos de chat',
            'chatflow.create' => 'Crear flujos de chat',
            'chatflow.update' => 'Actualizar flujos de chat',
            'chatflow.delete' => 'Eliminar flujos de chat',
            'chatflow.manage' => 'Gestionar flujos de chat completamente',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }
    }
}
