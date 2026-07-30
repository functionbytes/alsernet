<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Webhook;
use Modules\Helpdesk\Models\WebhookDelivery;
use Modules\HelpdeskAgents\Services\AiUsageRecorder;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Snapshot de salud operativa del panel helpdesk: profundidad de colas,
 * dead-letters (failed_jobs), webhooks fallidos, breaches SLA de la última
 * hora y tickets sin asignar con SLA próximo. Lo refresca el comando
 * programado helpdesk:ops-metrics (cada 5 min) y lo consume el dashboard de
 * reports y el sistema de alertas. Cada sonda es best-effort: si una fuente
 * no está disponible (módulo apagado, tabla ausente) devuelve null para esa
 * métrica en lugar de romper el snapshot.
 */
class OpsHealthService
{
    public const CACHE_KEY = 'helpdesk:ops:metrics';

    public const CACHE_TTL = 900; // 15 min: sobrevive a un scheduler caído sin quedarse eterno

    /**
     * Compute a fresh snapshot.
     *
     * @return array{
     *     generated_at: string,
     *     queues: array<string, int>,
     *     queue_total: int,
     *     failed_jobs: int|null,
     *     webhooks_failing: int|null,
     *     webhook_failed_deliveries_last_hour: int|null,
     *     sla_breaches_last_hour: int|null,
     *     unassigned_sla_warning: int,
     *     ai_today: array{calls: int, tokens: int}|null
     * }
     */
    public function snapshot(): array
    {
        $queues = $this->queueDepths();

        return [
            'generated_at' => now()->toIso8601String(),
            'queues' => $queues,
            'queue_total' => array_sum($queues),
            'failed_jobs' => $this->failedJobsCount(),
            'webhooks_failing' => $this->failingWebhookEndpoints(),
            'webhook_failed_deliveries_last_hour' => $this->failedWebhookDeliveriesLastHour(),
            'sla_breaches_last_hour' => $this->slaBreachesLastHour(),
            'unassigned_sla_warning' => $this->unassignedTicketsNearSlaBreach(),
            'ai_today' => $this->aiUsageToday(),
        ];
    }

    /**
     * Refresh the cached snapshot (called by the scheduled command).
     */
    public function refresh(): array
    {
        $snapshot = $this->snapshot();

        Cache::put(self::CACHE_KEY, $snapshot, self::CACHE_TTL);

        return $snapshot;
    }

    /**
     * Cached snapshot for dashboards; computes one if the scheduler has not
     * run yet.
     */
    public function cached(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, fn () => $this->snapshot());
    }

    /**
     * Umbrales superados según config('helpdesktickets.ops.alerts').
     *
     * @return list<string> Razones legibles (vacío = todo en orden).
     */
    public function evaluateAlerts(array $snapshot): array
    {
        $alerts = config('helpdesktickets.ops.alerts', []);
        $reasons = [];

        $queueThreshold = (int) ($alerts['queue_depth'] ?? 500);

        foreach ($snapshot['queues'] ?? [] as $queue => $depth) {
            if ($queueThreshold > 0 && $depth > $queueThreshold) {
                $reasons[] = "Cola '{$queue}' con {$depth} jobs pendientes (umbral {$queueThreshold}).";
            }
        }

        $failedThreshold = (int) ($alerts['failed_jobs'] ?? 0);
        $failedJobs = $snapshot['failed_jobs'] ?? null;

        if ($failedJobs !== null && $failedJobs > $failedThreshold) {
            $reasons[] = "{$failedJobs} jobs en dead-letter (failed_jobs, umbral {$failedThreshold}).";
        }

        $breachThreshold = (int) ($alerts['sla_breaches_per_hour'] ?? 10);
        $breaches = $snapshot['sla_breaches_last_hour'] ?? null;

        if ($breaches !== null && $breachThreshold > 0 && $breaches > $breachThreshold) {
            $reasons[] = "{$breaches} breaches de SLA en la última hora (umbral {$breachThreshold}).";
        }

        return $reasons;
    }

    /* ── Probes (best-effort) ────────────────────────────────────────────── */

    /**
     * @return array<string, int>
     */
    private function queueDepths(): array
    {
        $names = (array) config('helpdesktickets.ops.queues', []);
        $depths = [];

        foreach ($names as $name) {
            try {
                $depths[$name] = (int) Queue::size($name);
            } catch (\Throwable) {
                // driver sin soporte de size() o backend caído: se omite la cola
            }
        }

        return $depths;
    }

    private function failedJobsCount(): ?int
    {
        try {
            return (int) DB::table(config('queue.failed.table', 'failed_jobs'))->count();
        } catch (\Throwable) {
            return null;
        }
    }

    private function failingWebhookEndpoints(): ?int
    {
        if (! class_exists(Webhook::class)) {
            return null;
        }

        try {
            return (int) Webhook::query()
                ->where('is_active', true)
                ->whereNotNull('last_error')
                ->count();
        } catch (\Throwable) {
            return null;
        }
    }

    private function failedWebhookDeliveriesLastHour(): ?int
    {
        if (! class_exists(WebhookDelivery::class)) {
            return null;
        }

        try {
            // No hay tabla dead-letter de webhooks: una entrega fallida es una
            // fila sin delivered_at o con response_status >= 400.
            return (int) WebhookDelivery::query()
                ->where('created_at', '>=', now()->subHour())
                ->where(function ($q) {
                    $q->whereNull('delivered_at')
                        ->orWhere('response_status', '>=', 400);
                })
                ->count();
        } catch (\Throwable) {
            return null;
        }
    }

    private function slaBreachesLastHour(): ?int
    {
        if (! class_exists(ConversationSlaBreach::class)) {
            return null;
        }

        try {
            return (int) ConversationSlaBreach::query()
                ->where('breached_at', '>=', now()->subHour())
                ->count();
        } catch (\Throwable) {
            return null;
        }
    }

    private function unassignedTicketsNearSlaBreach(): int
    {
        try {
            $minutes = (int) config('helpdesktickets.ops.sla_warning_minutes', 60);

            return (int) Ticket::query()
                ->whereNull('assignee_id')
                ->whereNull('closed_at')
                ->slaWarning($minutes)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{calls: int, tokens: int}|null
     */
    private function aiUsageToday(): ?array
    {
        if (! class_exists(AiUsageRecorder::class)) {
            return null;
        }

        try {
            return app(AiUsageRecorder::class)->todayTotals();
        } catch (\Throwable) {
            return null;
        }
    }
}
