<?php

namespace Modules\HelpdeskPrestashop\Console\Commands;

use Illuminate\Console\Command;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

class TestConnectionCommand extends Command
{
    protected $signature = 'helpdeskprestashop:test-connection {--verbose-config : Muestra detalles de configuración (URL y estado del secreto)}';

    protected $description = 'Verifica conectividad y firma HMAC con el módulo PS alsernetbridge';

    public function handle(PrestashopContextService $service): int
    {
        $this->info('Probando conexión con PrestaShop...');

        $result = $service->testConnection();

        if ($this->option('verbose-config') || app()->environment('local', 'testing')) {
            $this->line('  URL: '.config('helpdeskprestashop.api_url'));
            $this->line('  Secreto: '.(config('helpdeskprestashop.webhook_secret') ? 'configurado' : 'VACÍO'));
        }

        $this->line('  Latencia: '.$result['latency_ms'].' ms');

        if ($result['ok']) {
            $this->info('  Estado: OK');

            return self::SUCCESS;
        }

        $this->error('  Estado: ERROR — '.($result['error'] ?? 'desconocido'));

        return self::FAILURE;
    }
}
