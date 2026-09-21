<?php

namespace Modules\HelpdeskLivechat\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskLivechatPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'helpdesk.livechat.settings.view' => 'Ver configuración de chat en vivo',
            'helpdesk.livechat.settings.update' => 'Actualizar configuración de chat en vivo',
            'helpdesk.pre-chat.manage' => 'Gestionar formulario previo al chat',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $adminRoles = Role::whereIn('name', ['admin', 'super-admin', 'super-administrador'])->get();

        foreach ($adminRoles as $role) {
            $role->givePermissionTo(array_keys($permissions));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
