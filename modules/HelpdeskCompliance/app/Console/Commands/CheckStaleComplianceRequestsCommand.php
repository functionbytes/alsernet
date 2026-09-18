<?php

namespace Modules\HelpdeskCompliance\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\AuditLogService;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;
use Modules\HelpdeskCompliance\Notifications\StaleComplianceRequestsDetected;
use Throwable;

/**
 * Un ComplianceRequest en 'pending' más allá del umbral configurado
 * (helpdeskcompliance.stale_pending_hours, default 24h) significa que el
 * borrado core (irreversible, ya aplicado) no ha terminado su cascada al
 * resto de módulos — el worker de la cola 'helpdeskcompliance' está parado o
 * el job murió sin llegar a marcar la solicitud como 'failed'. En este
 * entorno ya ha habido workers detenidos varias horas sin que nadie se diera
 * cuenta (ver reference_webadmin_worker_helpdesk_stopped_2026_08_28), así que
 * esto no puede depender solo de que alguien mire el panel de Solicitudes GDPR.
 *
 * Debounce igual que CheckEmailReputationCommand: solo notifica en la
 * transición "sin estancadas" -> "hay estancadas", no en cada ejecución
 * mientras el problema sigue vivo (evita spam del scheduler cada pocos
 * minutos); se resetea en cuanto no quedan solicitudes estancadas.
 */
class CheckStaleComplianceRequestsCommand extends Command
{
    protected $signature = 'helpdeskcompliance:check-stale-requests';

    protected $description = 'Detecta ComplianceRequest en pending estancadas y avisa a los administradores';

    private const DEBOUNCE_SETTING = 'compliance.stale_requests_breached';

    public function handle(): int
    {
        $thresholdHours = (int) config('helpdeskcompliance.stale_pending_hours', 24);

        $stale = ComplianceRequest::query()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours($thresholdHours))
            ->get(['id', 'customer_id', 'type', 'created_at']);

        $wasBreached = Setting::get(self::DEBOUNCE_SETTING, '0') === '1';
        $isBreached = $stale->isNotEmpty();

        Setting::set(self::DEBOUNCE_SETTING, $isBreached ? '1' : '0');

        if (! $isBreached) {
            $this->info('Sin solicitudes GDPR estancadas.');

            return self::SUCCESS;
        }

        $this->warn("{$stale->count()} solicitud(es) GDPR llevan más de {$thresholdHours}h en 'pending'.");

        foreach ($stale as $request) {
            Log::critical('ComplianceRequest estancada: cascada GDPR sin completar', [
                'compliance_request_id' => $request->id,
                'customer_id' => $request->customer_id,
                'type' => $request->type,
                'pending_since' => $request->created_at?->toIso8601String(),
            ]);

            AuditLogService::record('gdpr.cascade.stalled', $request, [], [
                'threshold_hours' => $thresholdHours,
            ]);
        }

        if (! $wasBreached) {
            $this->notifyAdmins($stale->pluck('id')->all(), $thresholdHours);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, int>  $requestIds
     */
    private function notifyAdmins(array $requestIds, int $thresholdHours): void
    {
        try {
            $admins = User::role(['manager', 'super-admin'])->get();
        } catch (Throwable $e) {
            Log::warning('helpdeskcompliance:check-stale-requests: no se pudieron obtener administradores para notificar', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new StaleComplianceRequestsDetected($requestIds, $thresholdHours));
    }
}
