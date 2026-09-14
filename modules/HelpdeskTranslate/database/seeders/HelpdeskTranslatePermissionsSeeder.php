<?php

namespace Modules\HelpdeskTranslate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpdeskTranslatePermissionsSeeder extends Seeder
{
    /** Admin-tier roles that always receive every HelpdeskTranslate permission. */
    private const ADMIN_ROLES = ['admin', 'super-admin', 'super-administrador'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $usePermissions = ['helpdesk-translate.use'];
        $settingsPermissions = [
            'helpdesk-translate.settings.view',
            'helpdesk-translate.settings.update',
        ];

        foreach ([...$usePermissions, ...$settingsPermissions] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // `helpdesk-translate.use` mirrors who can open helpdesk conversations:
        // anyone working the inbox should be able to translate messages.
        $this->grantTo($usePermissions, $this->rolesWith('helpdesk.conversations.view'));

        // `helpdesk-translate.settings.*` mirrors helpdesk back-office access
        // (DeepL keys, provider, auto-translate toggles).
        $this->grantTo($settingsPermissions, $this->rolesWith('helpdesk.helpcenter.view'));

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Roles holding the given permission, plus the admin-tier roles as a
     * fallback for fresh installs where the Helpdesk seeders haven't run yet.
     *
     * @return Collection<int, Role>
     */
    private function rolesWith(string $permissionName): Collection
    {
        $mirrored = Permission::where('name', $permissionName)->first()?->roles ?? collect();
        $admins = Role::whereIn('name', self::ADMIN_ROLES)->get();

        return $mirrored->merge($admins)->unique('id')->values();
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  Collection<int, Role>  $roles
     */
    private function grantTo(array $permissions, Collection $roles): void
    {
        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }
    }
}
