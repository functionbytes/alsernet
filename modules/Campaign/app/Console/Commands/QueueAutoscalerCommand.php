<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Modules\Campaign\Services\QueueAutoscaler;

class QueueAutoscalerCommand extends Command
{
    protected $signature = 'campaign:queue-check {--queue=campaign : Nombre de la cola}';

    protected $description = 'Monitorea la cola y recomienda escalado';

    public function handle(): int
    {
        $scaler = new QueueAutoscaler;
        $check = $scaler->check($this->option('queue'));

        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Cola', $check['queue']],
                ['Jobs pendientes', $check['pending_jobs']],
                ['Espera promedio (min)', $check['avg_wait_minutes']],
                ['Workers actuales', $check['current_workers']],
                ['Workers recomendados', $check['recommended_workers']],
                ['Recomendación', $check['recommendation']],
            ]
        );

        return self::SUCCESS;
    }
}
