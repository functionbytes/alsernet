<?php

namespace Modules\Engagement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Engagement\Models\PlatformIntegration;
use Modules\Engagement\Models\WebhookLog;
use Modules\Engagement\Notifications\IntegrationHealthAlert;

class CheckIntegrationHealthCommand extends Command
{
    protected $signature = 'engagement:check-health
                            {--silence-hours=24 : Hours of silence before alerting}
                            {--dead-rate-pct=5 : % of dead webhooks in last hour to trigger alert}';

    protected $description = 'Check platform integrations health and emit alerts for stale or failing ones.';

    public function handle(): int
    {
        $silenceHours = (int) $this->option('silence-hours');
        $deadRatePct = (float) $this->option('dead-rate-pct');

        $integrations = PlatformIntegration::query()->active()->get();

        $issues = 0;

        foreach ($integrations as $integration) {
            // Check 1: silence — no events received in N hours despite being active
            $hoursSinceLast = $integration->last_event_at
                ? $integration->last_event_at->diffInHours(now())
                : null;

            if ($hoursSinceLast !== null && $hoursSinceLast >= $silenceHours) {
                $this->warn("[silence] integration #{$integration->id} ({$integration->platform}) — {$hoursSinceLast}h sin eventos");
                Log::channel('daily')->warning('Integration silence detected', [
                    'integration_id' => $integration->id,
                    'platform' => $integration->platform,
                    'hours' => $hoursSinceLast,
                ]);
                $this->notifyAdmins($integration, "Sin eventos durante {$hoursSinceLast} horas", [
                    'hours_since_last' => $hoursSinceLast,
                ]);
                $issues++;
            }

            // Check 2: dead webhook rate
            $totalLastHour = WebhookLog::query()
                ->where('platform_integration_id', $integration->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($totalLastHour > 10) {
                $deadCount = WebhookLog::query()
                    ->where('platform_integration_id', $integration->id)
                    ->where('created_at', '>=', now()->subHour())
                    ->where('status', WebhookLog::STATUS_DEAD)
                    ->count();

                $rate = ($deadCount / $totalLastHour) * 100;
                if ($rate >= $deadRatePct) {
                    $this->error("[dead-rate] integration #{$integration->id} — {$rate}% dead ({$deadCount}/{$totalLastHour})");
                    Log::channel('daily')->error('Integration dead-rate threshold crossed', [
                        'integration_id' => $integration->id,
                        'platform' => $integration->platform,
                        'rate_pct' => round($rate, 2),
                        'dead' => $deadCount,
                        'total' => $totalLastHour,
                    ]);
                    $this->notifyAdmins($integration, "Tasa de webhooks muertos: {$rate}% en última hora", [
                        'rate_pct' => round($rate, 2),
                        'dead' => $deadCount,
                        'total' => $totalLastHour,
                    ]);
                    $issues++;
                }
            }
        }

        $this->info("Health check finished — {$integrations->count()} integraciones revisadas, {$issues} alertas");

        return self::SUCCESS;
    }

    private function notifyAdmins(PlatformIntegration $integration, string $issue, array $details): void
    {
        $userClass = config('auth.providers.users.model');
        if (! $userClass) {
            return;
        }

        $admins = $userClass::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->orWhere(function ($q) {
                $q->whereHas('permissions', fn ($q2) => $q2->where('name', 'engagement.manage'));
            })
            ->limit(20)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new IntegrationHealthAlert($integration, $issue, $details));
    }
}
