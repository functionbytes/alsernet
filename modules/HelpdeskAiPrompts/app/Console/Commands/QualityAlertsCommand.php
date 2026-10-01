<?php

namespace Modules\HelpdeskAiPrompts\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskAiPrompts\Models\AiPromptCase;
use Modules\HelpdeskAiPrompts\Notifications\AiPromptQualityAlertNotification;
use Modules\HelpdeskAiPrompts\Services\Quality\QualityAlertEvaluator;
use Throwable;

/**
 * Avisa a quien gestiona la biblioteca de prompts cuando un caso supera un
 * umbral de calidad en las últimas 24 h. El mismo aviso (caso + tipo) no se
 * repite hasta pasado el cooldown (24 h), aunque el caso siga mal.
 */
class QualityAlertsCommand extends Command
{
    protected $signature = 'ai-prompts:quality-alerts {--dry-run : Solo muestra las alertas, sin notificar}';

    protected $description = 'Notifica los casos del asistente IA que superan umbrales de escalada, 👎 o coste diario';

    public function handle(QualityAlertEvaluator $evaluator): int
    {
        $alerts = $evaluator->evaluate();

        if ($alerts->isEmpty()) {
            $this->info('Ningún caso supera los umbrales.');

            return self::SUCCESS;
        }

        $names = AiPromptCase::query()->whereIn('key', $alerts->pluck('case_key'))->pluck('name', 'key');
        $recipients = $this->option('dry-run') ? collect() : $this->recipients();
        $sent = 0;

        foreach ($alerts as $alert) {
            $label = "{$alert['case_key']} [{$alert['type']}] {$alert['value']} (umbral {$alert['threshold']})";

            if ($this->option('dry-run')) {
                $this->line("Alerta: {$label}");

                continue;
            }

            if (! $this->claim($alert)) {
                $this->line("Ya avisada, se omite: {$label}");

                continue;
            }

            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new AiPromptQualityAlertNotification($alert, $names[$alert['case_key']] ?? $alert['case_key']));
            }

            $sent++;
            $this->line("Avisada: {$label}");
        }

        $this->info("Alertas: {$alerts->count()}, notificadas: {$sent}.");

        return self::SUCCESS;
    }

    /**
     * Reserva atómica del aviso: false si ya se envió dentro del cooldown.
     *
     * @param  array{case_key: string, type: string}  $alert
     */
    private function claim(array $alert): bool
    {
        $hours = (int) config('helpdeskaiprompts_quality.alerts.cooldown_hours');

        return Cache::add("ai-prompts:quality-alert:{$alert['case_key']}:{$alert['type']}", now()->toIso8601String(), now()->addHours($hours));
    }

    /**
     * @return Collection<int, User>
     */
    private function recipients(): Collection
    {
        try {
            return User::permission('helpdesk.ai-prompts.manage')->get();
        } catch (Throwable $e) {
            Log::warning('ai-prompts:quality-alerts: no se pudieron obtener los destinatarios', ['error' => $e->getMessage()]);

            return collect();
        }
    }
}
