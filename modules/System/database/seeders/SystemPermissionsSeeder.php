<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SystemPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'system.cache.view',
            'system.cache.clear',
            'system.cache.write',
            'system.supervisor.read',
            'system.supervisor.execute',
            'system.queue.view',
            'system.queue.manage',
            'system.maintenance.view',
            'system.maintenance.toggle',
            'system.uploading.view',
            'system.uploading.update',
            'system.settings.view',
            'system.settings.update',
            'system.localization.view',
            'system.localization.update',
            'system.translations.view',
            'system.translations.update',
            'system.langs.view',
            'system.langs.manage',
            'system.access.view',
            'system.access.delete',
            'system.access.download',
            'system.info.view',
            'system.global-search.use',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
