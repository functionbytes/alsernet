<?php

namespace App\Console\Commands;

use App\Services\AlertThresholdService;
use Illuminate\Console\Command;

class CheckAlertThresholdsCommand extends Command
{
    protected $signature = 'alerts:check';

    protected $description = 'Verifica los umbrales de alerta configurados';

    public function handle(AlertThresholdService $service): int
    {
        $this->info('Verificando umbrales de alerta...');

        $service->checkAll();

        $this->info('Verificación completada.');

        return self::SUCCESS;
    }
}
