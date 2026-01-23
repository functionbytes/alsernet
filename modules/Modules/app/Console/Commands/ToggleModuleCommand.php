<?php

namespace Modules\Modules\Console\Commands;

use Illuminate\Console\Command;
use Nwidart\Modules\Facades\Module;

class ToggleModuleCommand extends Command
{
    protected $signature = 'module:toggle {module : The module name or alias} {--action=toggle : Action: toggle, enable, disable}';

    protected $description = 'Toggle the status of a module (enable/disable)';

    public function handle(): int
    {
        $moduleIdentifier = $this->argument('module');
        $action = $this->option('action');

        $module = Module::find($moduleIdentifier);

        if (! $module) {
            $this->error("Module '{$moduleIdentifier}' not found.");

            return self::FAILURE;
        }

        // Prevent toggling core modules
        $coreModules = ['Role', 'Modules'];
        if (in_array($module->getName(), $coreModules)) {
            $this->error("Cannot toggle core module '{$module->getName()}'.");

            return self::FAILURE;
        }

        try {
            if ($action === 'toggle') {
                if ($module->isEnabled()) {
                    $module->disable();
                    $this->info("Module '{$module->getName()}' has been disabled.");
                } else {
                    $module->enable();
                    $this->info("Module '{$module->getName()}' has been enabled.");
                }
            } elseif ($action === 'enable') {
                if ($module->isEnabled()) {
                    $this->warn("Module '{$module->getName()}' is already enabled.");
                } else {
                    $module->enable();
                    $this->info("Module '{$module->getName()}' has been enabled.");
                }
            } elseif ($action === 'disable') {
                if ($module->isDisabled()) {
                    $this->warn("Module '{$module->getName()}' is already disabled.");
                } else {
                    $module->disable();
                    $this->info("Module '{$module->getName()}' has been disabled.");
                }
            } else {
                $this->error("Invalid action. Use 'toggle', 'enable', or 'disable'.");

                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
