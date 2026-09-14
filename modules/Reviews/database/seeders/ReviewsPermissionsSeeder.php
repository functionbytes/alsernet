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
        $permisos = ['reviews.view', 'reviews.moderate', 'reviews.settings'];

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
