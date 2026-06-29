<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Models\Automation\Automation;

class DispatchAutomationJobsCommand extends Command
{
    protected $signature = 'campaign:dispatch-automations';

    protected $description = 'Recorre las automatizaciones activas y dispara sus jobs pendientes.';

    public function handle(): int
    {
        $this->info('Despachando jobs de automatizaciones activas…');

        if (method_exists(Automation::class, 'run')) {
            Automation::run();
        } else {
            $this->warn('Automation::run() no existe — método pendiente de portar (Fase 3).');
        }

        return self::SUCCESS;
    }
}
