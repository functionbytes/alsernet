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

        $permisos = [
            'questions.view' => 'Ver preguntas',
            'questions.moderate' => 'Moderar preguntas (responder, publicar, ocultar)',
        ];

        foreach ($permisos as $nombre => $descripcion) {
            Permission::updateOrCreate(
                ['name' => $nombre, 'guard_name' => 'web'],
                ['description' => $descripcion],
            );
        }

        foreach (self::ADMIN_ROLES as $nombre) {
            $rol = Role::where('name', $nombre)->where('guard_name', 'web')->first();

            if ($rol) {
                $rol->givePermissionTo(array_keys($permisos));
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
