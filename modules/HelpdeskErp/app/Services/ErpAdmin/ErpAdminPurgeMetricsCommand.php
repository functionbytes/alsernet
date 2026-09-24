<?php

namespace Modules\HelpdeskErp\Services\ErpAdmin;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\HelpdeskErp\Models\ErpAdminMetricEvent;

/**
 * Borra los eventos de «Métricas de Gestión» más antiguos que la retención
 * (config helpdeskErp.ext.admin.metrics.retention_days, editable en
 * «Ajustes de Gestión»). Programado a diario por ErpChatExtServiceProvider.
 * Borra en lotes para no bloquear la tabla.
 */
class ErpAdminPurgeMetricsCommand extends Command
{
    protected $signature = 'helpdeskerp:purge-metrics {--days= : Días a conservar (por defecto, la retención configurada)}';

    protected $description = 'Borra los eventos de métricas de Gestión (ERP) más antiguos que la retención';

    public function handle(): int
    {
        if (! Schema::connection('helpdesk')->hasTable('helpdesk_erp_metrics_events')) {
            $this->info('La tabla de métricas no existe todavía (migración pendiente).');

            return self::SUCCESS;
        }

        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('helpdeskErp.ext.admin.metrics.retention_days', 90);

        if ($days < 1) {
            $this->error('La retención tiene que ser de al menos 1 día.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days)->startOfDay();
        $total = 0;

        do {
            $ids = ErpAdminMetricEvent::query()
                ->where('occurred_at', '<', $cutoff)
                ->orderBy('id')
                ->limit(5000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $total += ErpAdminMetricEvent::query()->whereIn('id', $ids)->delete();
        } while ($ids->count() === 5000);

        $this->info("Eventos de métricas borrados: {$total} (anteriores al {$cutoff->format('d/m/Y')}).");

        return self::SUCCESS;
    }
}
