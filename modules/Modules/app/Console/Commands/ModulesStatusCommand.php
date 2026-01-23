<?php

namespace Modules\Modules\Console\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;

class ModulesStatusCommand extends Command
{
    protected $signature = 'modules:status';

    protected $description = 'Display the status of all modules';

    public function handle(): int
    {
        $this->info('Module Status Report');
        $this->line(str_repeat('=', 80));

        $modules = [];
        $enabledCount = 0;
        $disabledCount = 0;

        foreach (Module::all() as $module) {
            $isEnabled = $module->isEnabled();
            if ($isEnabled) {
                $enabledCount++;
            } else {
                $disabledCount++;
            }

            $moduleConfig = $this->getModuleConfig($module);
            $version = $moduleConfig['version'] ?? '1.0.0';
            $priority = $moduleConfig['priority'] ?? 0;

            $modules[] = [
                $module->getName(),
                strtolower($module->getName()),
                $isEnabled ? 'Enabled' : 'Disabled',
                $priority,
                $version,
            ];
        }

        $this->table(
            ['Name', 'Alias', 'Status', 'Priority', 'Version'],
            $modules
        );

        $this->line(str_repeat('=', 80));
        $this->info('Total Modules: '.count($modules));
        $this->line("  <fg=green>Enabled:</> {$enabledCount}");
        $this->line("  <fg=yellow>Disabled:</> {$disabledCount}");

        return self::SUCCESS;
    }

    /**
     * Get module configuration from module.json.
     */
    private function getModuleConfig($module): array
    {
        try {
            $configPath = $module->getPath().DIRECTORY_SEPARATOR.'module.json';
            if (file_exists($configPath)) {
                return json_decode(file_get_contents($configPath), true) ?? [];
            }
        } catch (\Exception $e) {
            // Return empty array if config cannot be read
        }

        return [];
    }
}
