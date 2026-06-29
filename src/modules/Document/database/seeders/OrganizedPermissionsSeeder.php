<?php

namespace Modules\Document\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OrganizedPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener configuración de permisos
        $permissionsConfig = config('permissions');

        $createdCount = 0;
        $skippedCount = 0;

        echo "\n═══════════════════════════════════════════════════════════════\n";
        echo "CREANDO PERMISOS ORGANIZADOS POR MÓDULO\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";

        foreach ($permissionsConfig as $moduleKey => $moduleConfig) {
            echo "📦 Módulo: {$moduleConfig['name']}\n";

            if ($moduleKey === 'modules') {
                // Permisos especiales de módulos
                foreach ($moduleConfig['permissions']['view'] as $moduleId => $description) {
                    $permissionName = "modules.view.{$moduleId}";

                    $permission = Permission::firstOrCreate(
                        ['name' => $permissionName, 'guard_name' => 'web'],
                        ['description' => $description]
                    );

                    if ($permission->wasRecentlyCreated) {
                        echo "   ✓ $permissionName\n";
                        $createdCount++;
                    } else {
                        $skippedCount++;
                    }
                }
            } else {
                // Permisos normales por módulo - Soporta dos niveles de anidación
                // Nivel 1: action (view, create, etc.) -> scopes (all, own, team)
                // Nivel 2: category (files, types, etc.) -> actions (view, upload, delete)
                foreach ($moduleConfig['permissions'] as $action => $actionConfig) {
                    // Detectar si es una categoría o un action directo
                    $isCategory = $this->isCategory($actionConfig);

                    if ($isCategory) {
                        // Estructura de categoría: {module}.{category}.{action}
                        foreach ($actionConfig as $subAction => $description) {
                            $permissionName = "{$moduleKey}.{$action}.{$subAction}";

                            $permission = Permission::firstOrCreate(
                                ['name' => $permissionName, 'guard_name' => 'web'],
                                ['description' => $description]
                            );

                            if ($permission->wasRecentlyCreated) {
                                echo "   ✓ $permissionName\n";
                                $createdCount++;
                            } else {
                                $skippedCount++;
                            }
                        }
                    } else {
                        // Estructura de scope: {module}.{action}.{scope}
                        foreach ($actionConfig as $scope => $description) {
                            $permissionName = "{$moduleKey}.{$action}.{$scope}";

                            $permission = Permission::firstOrCreate(
                                ['name' => $permissionName, 'guard_name' => 'web'],
                                ['description' => $description]
                            );

                            if ($permission->wasRecentlyCreated) {
                                echo "   ✓ $permissionName\n";
                                $createdCount++;
                            } else {
                                $skippedCount++;
                            }
                        }
                    }
                }
            }

            echo "\n";
        }

        echo "═══════════════════════════════════════════════════════════════\n";
        echo "✅ RESUMEN\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "   Permisos creados: {$createdCount}\n";
        echo "   Permisos existentes: {$skippedCount}\n";
        echo '   Total en BD: '.Permission::count()."\n\n";
    }

    /**
     * Detectar si una configuración de acción es una categoría o un scope directo
     *
     * Una categoría contiene sub-acciones como strings en los valores:
     * - 'files' => ['upload' => 'Descripción', 'download' => 'Descripción']
     *
     * Un scope directo contiene descripciones directas:
     * - 'view' => ['all' => 'Descripción', 'own' => 'Descripción']
     *
     * La diferencia se detecta por los valores:
     * - Si todos son strings: es una categoría (valores son descripciones directas)
     * - Si no es una categoría, es un scope
     */
    private function isCategory(array $config): bool
    {
        if (empty($config)) {
            return false;
        }

        // Palabras clave que indican un scope directo (no categoría)
        $scopeKeywords = ['all', 'own', 'team'];

        // Si la clave es un scope keyword, es scope directo
        foreach (array_keys($config) as $key) {
            if (in_array($key, $scopeKeywords, true)) {
                return false;
            }
        }

        // Si los valores NO son strings simples (ej. pueden ser arrays), es scope
        foreach ($config as $value) {
            if (is_array($value)) {
                return false;
            }
        }

        // Si todos los valores son strings simples, es una categoría
        return true;
    }
}
