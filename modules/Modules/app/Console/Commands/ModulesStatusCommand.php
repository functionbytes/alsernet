<?php

namespace Modules\Modules\Console\Commands;

use Illuminate\Console\Command;
use Modules\Modules\Services\ModuleConfigReader;
use Nwidart\Modules\Facades\Module;

class ModulesStatusCommand extends Command
{
    protected $signature = 'modules:status';

    protected $description = 'Muestra el estado de todos los módulos';

    public function __construct(private readonly ModuleConfigReader $configReader)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Reporte de estado de módulos');
        $this->line(str_repeat('=', 80));

        $rows = [];
        $enabledCount = 0;
        $disabledCount = 0;

        foreach (Module::all() as $module) {
            $isEnabled = $module->isEnabled();
            $isEnabled ? $enabledCount++ : $disabledCount++;

            $config = $this->configReader->read($module);

            $rows[] = [
                $module->getName(),
                $config['alias'] ?? strtolower($module->getName()),
                $isEnabled ? '<fg=green>Habilitado</>' : '<fg=yellow>Deshabilitado</>',
                $config['priority'] ?? 0,
                $config['version'] ?? '1.0.0',
            ];
        }

        $this->table(['Nombre', 'Alias', 'Estado', 'Prioridad', 'Versión'], $rows);

        $this->line(str_repeat('=', 80));
        $this->info('Total: '.count($rows)." | Habilitados: {$enabledCount} | Deshabilitados: {$disabledCount}");

        return self::SUCCESS;
    }
}
