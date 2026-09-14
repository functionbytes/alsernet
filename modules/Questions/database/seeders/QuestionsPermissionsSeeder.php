<?php

namespace Modules\Questions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class QuestionsPermissionsSeeder extends Seeder
{
    private const ADMIN_ROLES = ['admin', 'super-admin', 'super-administrador'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = ['questions.view', 'questions.moderate'];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
        }

        foreach (self::ADMIN_ROLES as $nombre) {
            $rol = Role::where('name', $nombre)->where('guard_name', 'web')->first();

            if ($rol) {
                $rol->givePermissionTo($permisos);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
