<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Mail\OpsAlertMail;
use Modules\HelpdeskTickets\Services\OpsHealthService;

/**
 * Programado cada 5 min: refresca el snapshot de salud operativa del helpdesk
 * (colas, dead-letters, webhooks, SLA) en cache, lo deja en el log y — si las
 * alertas están activadas (OFF por defecto) y algún umbral se supera — envía
 * un mail encolado a los managers (permiso manage_helpdesk), con cooldown
 * para no repetir cada 5 minutos.
 */
class CollectOpsMetricsCommand extends Command
{
    private const ALERT_COOLDOWN_KEY = 'helpdesk:ops:alert-cooldown';

    protected $signature = 'helpdesk:ops-metrics {--no-alerts : Solo recolectar, sin evaluar alertas}';

    protected $description = 'Recolecta métricas operativas del helpdesk (colas, webhooks, SLA) y evalúa alertas';

    public function handle(OpsHealthService $ops): int
    {
        $snapshot = $ops->refresh();

        Log::info('Helpdesk ops metrics', $snapshot);

        $this->line('Snapshot de salud operativa actualizado:');
        $this->line(sprintf('  Colas: %d jobs pendientes (%s)', $snapshot['queue_total'], $this->formatQueues($snapshot['queues'])));
        $this->line(sprintf('  Dead-letters (failed_jobs): %s', $this->formatNullable($snapshot['failed_jobs'])));
        $this->line(sprintf('  Webhooks activos con error: %s', $this->formatNullable($snapshot['webhooks_failing'])));
        $this->line(sprintf('  Entregas webhook fallidas (1h): %s', $this->formatNullable($snapshot['webhook_failed_deliveries_last_hour'])));
        $this->line(sprintf('  Breaches SLA (1h): %s', $this->formatNullable($snapshot['sla_breaches_last_hour'])));
        $this->line(sprintf('  Tickets sin asignar con SLA próximo: %d', $snapshot['unassigned_sla_warning']));

        if ($this->option('no-alerts')) {
            return self::SUCCESS;
        }

        $this->evaluateAndNotify($ops, $snapshot);

        return self::SUCCESS;
    }

    private function evaluateAndNotify(OpsHealthService $ops, array $snapshot): void
    {
        if (! config('helpdesktickets.ops.alerts.enabled', false)) {
            return;
        }

        $reasons = $ops->evaluateAlerts($snapshot);

        if ($reasons === []) {
            return;
        }

        Log::warning('Helpdesk ops alert thresholds exceeded', ['reasons' => $reasons]);

        $cooldownMinutes = max(1, (int) config('helpdesktickets.ops.alerts.cooldown_minutes', 60));

        // Cache::add es atómico: solo el primer run dentro de la ventana envía.
        if (! Cache::add(self::ALERT_COOLDOWN_KEY, now()->toIso8601String(), now()->addMinutes($cooldownMinutes))) {
            $this->line('Alerta en cooldown, no se reenvía el mail.');

            return;
        }

        try {
            // Mismo criterio de destinatarios que SendSlaBreachNotification.
            $managers = User::permission('manage_helpdesk')->get();
        } catch (\Throwable $e) {
            Log::warning('Helpdesk ops alert: could not resolve managers', ['error' => $e->getMessage()]);

            return;
        }

        if ($managers->isEmpty()) {
            Log::warning('Helpdesk ops alert: no users with manage_helpdesk permission to notify');

            return;
        }

        $subject = '[Helpdesk] Alerta operativa: '.count($reasons).' umbral(es) superado(s)';
        $content = $this->buildContent($reasons, $snapshot);

        foreach ($managers as $manager) {
            if (empty($manager->email)) {
                continue;
            }

            Mail::to($manager->email, $manager->name ?? null)->queue(new OpsAlertMail($subject, $content));
        }

        $this->warn('Alerta operativa enviada a '.$managers->count().' manager(s).');
    }

    /**
     * @param  list<string>  $reasons
     */
    private function buildContent(array $reasons, array $snapshot): string
    {
        $items = implode('', array_map(
            fn (string $r) => '<li>'.e($r).'</li>',
            $reasons
        ));

        return '<h2>Alerta operativa del helpdesk</h2>'
            .'<p>Se han superado los siguientes umbrales:</p>'
            .'<ul>'.$items.'</ul>'
            .'<p>Estado actual: '
            .e(sprintf(
                '%d jobs en cola, %s dead-letters, %s breaches SLA en la última hora, %d tickets sin asignar con SLA próximo.',
                $snapshot['queue_total'],
                $this->formatNullable($snapshot['failed_jobs']),
                $this->formatNullable($snapshot['sla_breaches_last_hour']),
                $snapshot['unassigned_sla_warning']
            ))
            .'</p>'
            .'<p>Generado: '.e($snapshot['generated_at']).'</p>';
    }

    private function formatQueues(array $queues): string
    {
        if ($queues === []) {
            return 'sin colas monitorizadas';
        }

        return implode(', ', array_map(
            fn ($name, $depth) => "{$name}: {$depth}",
            array_keys($queues),
            array_values($queues)
        ));
    }

    private function formatNullable(?int $value): string
    {
        return $value === null ? 'n/d' : (string) $value;
    }
}
