<?php

namespace Modules\System\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SystemPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'system.cache.view' => 'Ver estado de la caché del sistema',
            'system.cache.clear' => 'Limpiar caché del sistema',
            'system.cache.write' => 'Escribir en la caché del sistema',
            'system.supervisor.read' => 'Ver procesos de Supervisor',
            'system.supervisor.execute' => 'Ejecutar acciones sobre Supervisor',
            'system.queue.view' => 'Ver colas del sistema',
            'system.queue.manage' => 'Gestionar colas del sistema',
            'system.maintenance.view' => 'Ver modo de mantenimiento',
            'system.maintenance.toggle' => 'Activar/desactivar modo de mantenimiento',
            'system.uploading.view' => 'Ver configuración de subida de archivos',
            'system.uploading.update' => 'Actualizar configuración de subida de archivos',
            'system.settings.view' => 'Ver configuración del sistema',
            'system.settings.update' => 'Actualizar configuración del sistema',
            'system.localization.view' => 'Ver configuración de localización',
            'system.localization.update' => 'Actualizar configuración de localización',
            'system.translations.view' => 'Ver traducciones del sistema',
            'system.translations.update' => 'Actualizar traducciones del sistema',
            'system.langs.view' => 'Ver idiomas del sistema',
            'system.langs.manage' => 'Gestionar idiomas del sistema',
            'system.access.view' => 'Ver registros de acceso',
            'system.access.delete' => 'Eliminar registros de acceso',
            'system.access.download' => 'Descargar registros de acceso',
            'system.info.view' => 'Ver información del sistema',
            'system.global-search.use' => 'Usar la búsqueda global',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        $superSettings = Role::where('name', 'super-settings')->first();
        if ($superSettings) {
            $superSettings->givePermissionTo(array_keys($permissions));
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
