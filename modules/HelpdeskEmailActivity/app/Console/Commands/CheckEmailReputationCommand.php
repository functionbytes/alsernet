<?php

namespace Modules\HelpdeskEmailActivity\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Notifications\ReputationThresholdBreached;
use Throwable;

/**
 * Recalcula la tasa de rebote/queja de los últimos N días (config
 * reputation_window_days) y notifica a manager/super-admin si cruza el
 * umbral crítico configurado — con el mismo debounce que
 * ProcessEmailBouncesCommand: solo al CRUZAR el umbral, no en cada corrida
 * mientras siga roto (se resetea al volver a estar por debajo).
 */
class CheckEmailReputationCommand extends Command
{
    protected $signature = 'email-logs:check-reputation';

    protected $description = 'Calcula la tasa de rebote/queja y avisa a los administradores si cruza el umbral crítico';

    public function handle(): int
    {
        $days = (int) Setting::get('helpdeskemailactivity.reputation_window_days', config('helpdeskemailactivity.reputation_window_days', 30));
        $stats = EmailLog::reputationStats($days);

        $bounceCritical = (float) Setting::get('helpdeskemailactivity.bounce_rate_critical_pct', config('helpdeskemailactivity.bounce_rate_critical_pct'));
        $complaintCritical = (float) Setting::get('helpdeskemailactivity.complaint_rate_critical_pct', config('helpdeskemailactivity.complaint_rate_critical_pct'));

        $this->info("Tasa de rebote: {$stats['bounce_rate']}% (umbral {$bounceCritical}%) — Tasa de queja: {$stats['complaint_rate']}% (umbral {$complaintCritical}%), sobre {$stats['attempted']} envíos en {$days} días.");

        $this->checkMetric('bounce_rate', $stats['bounce_rate'], $bounceCritical, 'rebote');
        $this->checkMetric('complaint_rate', $stats['complaint_rate'], $complaintCritical, 'queja');

        return self::SUCCESS;
    }

    private function checkMetric(string $key, float $rate, float $threshold, string $label): void
    {
        $wasBreached = Setting::get("helpdeskemailactivity.reputation_breached_{$key}", '0') === '1';
        $isBreached = $rate >= $threshold;

        Setting::set("helpdeskemailactivity.reputation_breached_{$key}", $isBreached ? '1' : '0');

        // Solo notifica en la transición false -> true (se acaba de cruzar).
        if ($isBreached && ! $wasBreached) {
            $this->notifyAdmins($key, $label, $rate, $threshold);
        }
    }

    private function notifyAdmins(string $metric, string $label, float $rate, float $threshold): void
    {
        try {
            $admins = User::role(['manager', 'super-admin'])->get();
        } catch (Throwable $e) {
            Log::warning('email-logs:check-reputation: no se pudieron obtener administradores para notificar', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new ReputationThresholdBreached($metric, $label, $rate, $threshold));
    }
}
