<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ModulePermissionsSeeder extends Seeder
{
    /**
     * Módulos disponibles en el sistema
     */
    private array $modules = [
        'documents' => 'Documentos',
        'mailers' => 'Emails',
        'media' => 'Gestor de Medios',
        'users' => 'Usuarios',
        'events' => 'Eventos',
        'warehouse' => 'Almacén',
        'webhooks' => 'Webhooks',
        'roles' => 'Roles y Permisos',
        'auth' => 'Seguridad',
        'notifications' => 'Notificaciones',
        'backups' => 'Copias de seguridad',
        'settings' => 'Configuraciones',
        'helpdesk' => 'Helpdesk',
        'campaigns' => 'Campañas',
        'suppliers' => 'Proveedores',
        'analytics' => 'Analytics',
    ];

    public function run(): void
    {
        // Crear permisos para cada módulo
        foreach ($this->modules as $moduleId => $moduleName) {
            Permission::firstOrCreate(
                ['name' => "modules.view.{$moduleId}", 'guard_name' => 'web'],
                ['description' => "Ver módulo de {$moduleName}"]
            );
        }

        $this->command->info('Permisos de módulos creados correctamente.');
    }
}
