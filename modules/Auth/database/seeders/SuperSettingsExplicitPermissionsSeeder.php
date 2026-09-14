<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Hace explícito lo que `super-settings` ya podía hacer.
 *
 * Hasta ahora, `Gate::before` en AuthServiceProvider devolvía true para
 * cualquier permiso si el usuario tenía el rol `super-settings`. Con 1.141
 * usuarios en ese rol, las Policies y los permisos de los 40 módulos eran
 * decorativos para la mayor parte del panel: se podía quitar un permiso desde
 * la interfaz de roles y no pasaba nada.
 *
 * Al retirar ese atajo, el rol se queda con lo que tenga asignado de verdad —y
 * tenía 61 de 447 permisos, así que habría perdido el 86 % del sistema de un
 * día para otro. Este seeder cierra esa diferencia ANTES de retirarlo: asigna
 * al rol los permisos que ya ejercía, de modo que el cambio no le quita nada a
 * nadie el primer día.
 *
 * A partir de aquí sí se puede recortar: quitarle un permiso al rol surte
 * efecto de inmediato, que es justo lo que antes no ocurría.
 *
 * Es idempotente: se puede volver a ejecutar cuando aparezcan permisos nuevos
 * (un módulo recién instalado siembra los suyos y este seeder los reparte).
 */
class SuperSettingsExplicitPermissionsSeeder extends Seeder
{
    /**
     * Los dos roles que gobernaban por atajo en vez de por permiso:
     * `super-settings` por el Gate::before de este módulo (retirado) y
     * `super-admin` por el de Document, que no acotaba por ability y decidía
     * sobre los 40 módulos (ya acotado a su propio dominio).
     */
    private const ROLES = ['super-settings', 'super-admin'];

    public function run(): void
    {
        // syncPermissions() con TODOS los permisos y no givePermissionTo() uno a
        // uno: son cientos de filas y la primera forma resuelve en dos
        // consultas. No quita nada, porque el conjunto es el total.
        $todos = Permission::query()->pluck('name')->all();

        foreach (self::ROLES as $nombre) {
            $role = Role::where('name', $nombre)->first();

            if (! $role) {
                $this->command?->warn("No existe el rol {$nombre}: se omite.");

                continue;
            }

            $antes = $role->permissions()->count();

            $role->syncPermissions($todos);

            $this->command?->info(sprintf(
                '%s: %d → %d permisos explícitos (%d nuevos).',
                $nombre,
                $antes,
                count($todos),
                count($todos) - $antes,
            ));
        }

        // La caché de Spatie sobrevive a la escritura: sin esto, los roles
        // siguen resolviendo con los permisos viejos hasta que expire.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
