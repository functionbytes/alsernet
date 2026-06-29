<?php

namespace Modules\Role\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync {--delete : Eliminar permisos no configurados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar permisos desde config/permissions.php';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('SINCRONIZANDO PERMISOS DESDE CONFIGURACIÓN');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        $permissionsConfig = config('permissions');
        $createdCount = 0;
        $updatedCount = 0;
        $configuredPermissions = [];

        foreach ($permissionsConfig as $moduleKey => $moduleConfig) {
            $this->line("📦 Módulo: <fg=cyan>{$moduleConfig['name']}</>");

            if ($moduleKey === 'modules') {
                foreach ($moduleConfig['permissions']['view'] as $moduleId => $description) {
                    $permissionName = "modules.view.{$moduleId}";
                    $configuredPermissions[] = $permissionName;

                    $permission = Permission::firstOrNew(
                        ['name' => $permissionName, 'guard_name' => 'web']
                    );

                    if (! $permission->exists) {
                        $permission->description = $description;
                        $permission->save();
                        $this->line("   <fg=green>✓</> {$permissionName}");
                        $createdCount++;
                    } elseif ($permission->description !== $description) {
                        $permission->update(['description' => $description]);
                        $this->line("   <fg=yellow>~</> {$permissionName}");
                        $updatedCount++;
                    }
                }
            } else {
                // Soporta dos niveles de anidación: acciones con scopes o categorías con sub-acciones
                foreach ($moduleConfig['permissions'] as $action => $actionConfig) {
                    $isCategory = $this->isCategory($actionConfig);

                    if ($isCategory) {
                        // Estructura de categoría: {module}.{category}.{action}
                        foreach ($actionConfig as $subAction => $description) {
                            $permissionName = "{$moduleKey}.{$action}.{$subAction}";
                            $configuredPermissions[] = $permissionName;

                            $permission = Permission::firstOrNew(
                                ['name' => $permissionName, 'guard_name' => 'web']
                            );

                            if (! $permission->exists) {
                                $permission->description = $description;
                                $permission->save();
                                $this->line("   <fg=green>✓</> {$permissionName}");
                                $createdCount++;
                            } elseif ($permission->description !== $description) {
                                $permission->update(['description' => $description]);
                                $this->line("   <fg=yellow>~</> {$permissionName}");
                                $updatedCount++;
                            }
                        }
                    } else {
                        // Estructura de scope: {module}.{action}.{scope}
                        foreach ($actionConfig as $scope => $description) {
                            $permissionName = "{$moduleKey}.{$action}.{$scope}";
                            $configuredPermissions[] = $permissionName;

                            $permission = Permission::firstOrNew(
                                ['name' => $permissionName, 'guard_name' => 'web']
                            );

                            if (! $permission->exists) {
                                $permission->description = $description;
                                $permission->save();
                                $this->line("   <fg=green>✓</> {$permissionName}");
                                $createdCount++;
                            } elseif ($permission->description !== $description) {
                                $permission->update(['description' => $description]);
                                $this->line("   <fg=yellow>~</> {$permissionName}");
                                $updatedCount++;
                            }
                        }
                    }
                }
            }

            $this->newLine();
        }

        // Eliminar permisos no configurados si se especifica
        $deletedCount = 0;
        if ($this->option('delete')) {
            $this->info('Eliminando permisos no configurados...');
            $unusedPermissions = Permission::whereNotIn('name', $configuredPermissions)
                ->where('guard_name', 'web')
                ->where('name', 'not like', 'documents.%')
                ->where('name', 'not like', 'document_%')
                ->get();

            foreach ($unusedPermissions as $permission) {
                $permission->delete();
                $this->line("   <fg=red>✗</> {$permission->name}");
                $deletedCount++;
            }
        }

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('✅ RESUMEN');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->line("   Creados: <fg=green>{$createdCount}</>");
        $this->line("   Actualizados: <fg=yellow>{$updatedCount}</>");
        if ($this->option('delete')) {
            $this->line("   Eliminados: <fg=red>{$deletedCount}</>");
        }
        $this->line('   Total en BD: <fg=cyan>'.Permission::count().'</>', "\n");

        return Command::SUCCESS;
    }

    /**
     * Detectar si una configuración de acción es una categoría o un scope directo
     *
     * Una categoría contiene sub-acciones como strings en los valores:
     * - 'files' => ['upload' => 'Descripción', 'download' => 'Descripción']
     *
     * Un scope directo contiene descripciones directas:
     * - 'view' => ['all' => 'Descripción', 'own' => 'Descripción']
     */
    private function isCategory(array $config): bool
    {
        if (empty($config)) {
            return false;
        }

        $scopeKeywords = ['all', 'own', 'team'];

        foreach (array_keys($config) as $key) {
            if (in_array($key, $scopeKeywords, true)) {
                return false;
            }
        }

        foreach ($config as $value) {
            if (is_array($value)) {
                return false;
            }
        }

        return true;
    }
}
