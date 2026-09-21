<?php

namespace Modules\Reviews\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos de la bandeja de opiniones.
 *
 * `reviews.view` para consultarla; `reviews.moderate` para publicar, retirar y
 * responder, que es lo que sale a la tienda y ve el cliente.
 */
class ReviewsPermissionsSeeder extends Seeder
{
    private const ADMIN_ROLES = ['admin', 'super-admin', 'super-administrador'];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 'reviews.settings' va aparte de 'moderate': quien modera decide qué se
        // publica, pero tocar las credenciales de las fichas de Google es otra cosa.
        $permisos = [
            'reviews.view' => 'Ver opiniones',
            'reviews.moderate' => 'Moderar opiniones (publicar, retirar, responder)',
            'reviews.settings' => 'Configurar credenciales de fichas de Google',
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
