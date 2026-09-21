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
            'helpdesk.helpcenter.view' => 'Ver centro de ayuda',
            // Articles
            'helpdesk.helpcenter.articles.view' => 'Ver artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.create' => 'Crear artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.update' => 'Actualizar artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.delete' => 'Eliminar artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.manage' => 'Gestionar artículos del centro de ayuda completamente',
            'helpdesk.helpcenter.articles.translate' => 'Traducir artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.embed' => 'Incrustar artículos del centro de ayuda',
            'helpdesk.helpcenter.articles.vote-moderate' => 'Moderar votos de artículos del centro de ayuda',
            // Categories
            'helpdesk.helpcenter.categories.view' => 'Ver categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.create' => 'Crear categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.update' => 'Actualizar categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.delete' => 'Eliminar categorías del centro de ayuda',
            'helpdesk.helpcenter.categories.manage' => 'Gestionar categorías del centro de ayuda completamente',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        foreach (['super-admin', 'super-settings', 'admin', 'manager'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(array_keys($permissions));
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('HelpdeskHelpcenter permissions: '.count($permissions).' creados.');
    }
}
