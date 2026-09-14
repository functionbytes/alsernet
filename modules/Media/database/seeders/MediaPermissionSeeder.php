<?php

namespace Modules\Media\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MediaPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionNames = [
            'media.view',
            'media.create',
            'media.update',
            'media.delete',
            'media.manage',
            'media.force-delete',
            'media.settings.view',
            'media.settings.update',
        ];

        $permissions = collect($permissionNames)->map(fn (string $name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]));

        $adminRole = Role::where('guard_name', 'web')
            ->whereIn('name', ['admin', 'super-admin', 'super-settings'])
            ->first();

        $adminRole?->givePermissionTo($permissions);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
