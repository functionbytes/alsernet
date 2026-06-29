<?php

namespace Modules\Modules\Console\Commands;

use Illuminate\Console\Command;
use Modules\Modules\Services\ModuleService;
use Nwidart\Modules\Facades\Module;

class ToggleModuleCommand extends Command
{
    protected $signature = 'module:toggle {module : Nombre o alias del módulo} {--action=toggle : Acción: toggle, enable, disable}';

    protected $description = 'Habilita, deshabilita o alterna el estado de un módulo';

    protected array $coreModules = ['Role', 'Modules'];

    public function __construct(private readonly ModuleService $moduleService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $moduleIdentifier = $this->argument('module');
        $action = $this->option('action');

        $module = Module::find($moduleIdentifier);

        if (! $module) {
            $this->error("Módulo '{$moduleIdentifier}' no encontrado.");

            return self::FAILURE;
        }

        if (in_array($module->getName(), $this->coreModules)) {
            $this->error("No se puede modificar el módulo principal '{$module->getName()}'.");

            return self::FAILURE;
        }

        try {
            match ($action) {
                'toggle' => $this->toggle($module),
                'enable' => $this->enable($module),
                'disable' => $this->disable($module),
                default => throw new \InvalidArgumentException("Acción inválida. Usa 'toggle', 'enable' o 'disable'."),
            };

            $this->moduleService->clearCache();

            return self::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    private function toggle(\Nwidart\Modules\Module $module): void
    {
        if ($module->isEnabled()) {
            $module->disable();
            $this->info("Módulo '{$module->getName()}' deshabilitado.");
        } else {
            $module->enable();
            $this->info("Módulo '{$module->getName()}' habilitado.");
        }
    }

    private function enable(\Nwidart\Modules\Module $module): void
    {
        if ($module->isEnabled()) {
            $this->warn("El módulo '{$module->getName()}' ya está habilitado.");

            return;
        }

        $module->enable();
        $this->info("Módulo '{$module->getName()}' habilitado.");
    }

    private function disable(\Nwidart\Modules\Module $module): void
    {
        if ($module->isDisabled()) {
            $this->warn("El módulo '{$module->getName()}' ya está deshabilitado.");

            return;
        }

        $module->disable();
        $this->info("Módulo '{$module->getName()}' deshabilitado.");
    }
}
