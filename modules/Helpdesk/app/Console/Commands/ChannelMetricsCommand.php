<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Broadcasting\ResilientBroadcaster;
use Modules\Helpdesk\Support\ChannelMetrics;

/**
 * Muestra los contadores diarios del pipeline de canales (entrantes y envíos
 * fallidos por canal). Útil para un vistazo rápido de salud/throughput.
 */
class ChannelMetricsCommand extends Command
{
    protected $signature = 'helpdesk:channel-metrics {--date= : Día YYYY-MM-DD (por defecto hoy)}';

    protected $description = 'Muestra los contadores del pipeline de canales (WhatsApp/Facebook/Instagram) de un día.';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->format('Y-m-d');

        $this->info("Métricas de canales — {$date}");

        $rows = [];
        foreach (ChannelMetrics::snapshot($date) as $channel => $metrics) {
            $rows[] = [$channel, $metrics['inbound'] ?? 0, $metrics['send_failed'] ?? 0];
        }

        $this->table(['Canal', 'Entrantes', 'Envíos fallidos'], $rows);

        // Fallos del transporte de tiempo real. No tumban ninguna petición (los
        // absorbe ResilientBroadcaster), así que sin este contador una caída del
        // servidor de websockets sólo se notaría como "el chat no llega en vivo".
        $realtimeFailures = ChannelMetrics::get(
            $date,
            ResilientBroadcaster::FAILURE_CHANNEL,
            ResilientBroadcaster::FAILURE_METRIC
        );

        if ($realtimeFailures > 0) {
            $this->warn("Websocket: {$realtimeFailures} emisiones fallidas — revisa que el servidor Reverb esté arriba.");
        } else {
            $this->info('Websocket: sin fallos de emisión.');
        }

        return self::SUCCESS;
    }
}
