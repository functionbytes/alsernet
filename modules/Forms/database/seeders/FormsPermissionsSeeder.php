<?php

namespace Modules\Forms\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FormsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->assignPermissionsToRoles();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function createPermissions(): void
    {
        foreach (config('forms.permissions', []) as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['flag'], 'guard_name' => 'web'],
                ['description' => $permission['label']],
            );
        }
    }

    private function assignPermissionsToRoles(): void
    {
        $all = Permission::where('name', 'like', 'Forms.%')->get();

        // super-admin no tiene Gate::before en este proyecto: sin asignación
        // explícita el módulo queda invisible incluso para él.
        $this->grant('super-admin', $all);
        $this->grant('super-settings', $all);

        $this->grant('settings', $all->where('name', '!=', 'Forms.settings.manage'));

        $this->grant('manager', $all->whereIn('name', [
            'Forms.forms.index',
            'Forms.forms.create',
            'Forms.forms.edit',
            'Forms.submissions.index',
            'Forms.submissions.export',
            'Forms.categories.manage',
            'Forms.analytics.index',
        ]));
    }

    /**
     * findByName() lanza RoleDoesNotExist si el rol no está sembrado, así que
     * el lookup va por query: un rol ausente se salta en silencio.
     */
    private function grant(string $roleName, iterable $permissions): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

        if (! $role) {
            return;
        }

        $role->givePermissionTo($permissions);
    }
}
