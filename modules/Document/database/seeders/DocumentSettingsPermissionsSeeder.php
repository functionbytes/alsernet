<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DocumentSettingsPermissionsSeeder extends Seeder
{
    /**
     * Permisos usados por SettingsPolicy (Settings > Documentos) que nunca
     * se habían sembrado: super-admin los sorteaba con un hasRole() propio
     * dentro del Policy, pero el rol manager no podía obtenerlos de ninguna
     * forma porque el permiso simplemente no existía en BD.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'documents.configure' => 'Configurar ajustes generales de documentos',
            'documents.manage-types' => 'Gestionar tipos de documento',
            'documents.manage-conditions' => 'Gestionar condiciones de documentos',
            'documents.manage-sla-policies' => 'Gestionar políticas de SLA de documentos',
            'documents.manage-groups' => 'Gestionar grupos de validadores de documentos',
            'documents.manage-blockades' => 'Gestionar bloqueos de documentos',
        ];

        foreach ($permissions as $name => $description) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['description' => $description],
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
