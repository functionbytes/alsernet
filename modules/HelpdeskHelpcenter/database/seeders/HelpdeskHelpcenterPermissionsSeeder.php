<?php

namespace Modules\HelpdeskHelpcenter\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskHelpcenterPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Module-wide
            'helpdesk.helpcenter.view',
            // Articles
            'helpdesk.helpcenter.articles.view',
            'helpdesk.helpcenter.articles.create',
            'helpdesk.helpcenter.articles.update',
            'helpdesk.helpcenter.articles.delete',
            'helpdesk.helpcenter.articles.manage',
            'helpdesk.helpcenter.articles.translate',
            'helpdesk.helpcenter.articles.embed',
            'helpdesk.helpcenter.articles.vote-moderate',
            // Categories
            'helpdesk.helpcenter.categories.view',
            'helpdesk.helpcenter.categories.create',
            'helpdesk.helpcenter.categories.update',
            'helpdesk.helpcenter.categories.delete',
            'helpdesk.helpcenter.categories.manage',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['super-admin', 'super-settings', 'admin', 'manager'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('HelpdeskHelpcenter permissions: '.count($permissions).' creados.');
    }
}
