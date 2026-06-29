<?php

namespace Modules\Campaign\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Models\Campaign;
use Modules\CampaignSendingServers\Models\SendingServer;

/**
 * Comando de alertas operacionales para el equipo de devops.
 * Revisa: colas atascadas, servidores caídos, campañas fallando, espacio en disco.
 */
class AlertCheckCommand extends Command
{
    protected $signature = 'campaign:alert-check
                            {--bounce-threshold=5 : Umbral % de rebotes para alerta}
                            {--queue-stuck-minutes=30 : Minutos sin procesar para considerar cola atascada}
                            {--disk-threshold=90 : Umbral % de disco para alerta}';

    protected $description = 'Revisa métricas operacionales y emite alertas si hay problemas';

    public function handle(): int
    {
        $alerts = [];

        $alerts = array_merge($alerts, $this->checkStuckCampaigns());
        $alerts = array_merge($alerts, $this->checkInactiveServers());
        $alerts = array_merge($alerts, $this->checkBounceRate());
        $alerts = array_merge($alerts, $this->checkDiskSpace());
        $alerts = array_merge($alerts, $this->checkQueueLag());

        if (empty($alerts)) {
            $this->info('Todas las métricas operacionales están dentro de límites aceptables.');

            return self::SUCCESS;
        }

        foreach ($alerts as $alert) {
            $this->error("[{$alert['severity']}] {$alert['message']}");
            Log::warning('Campaign alert', $alert);
        }

        // Notificar al canal configurado (Slack/Teams/email) si hay alertas críticas
        $critical = collect($alerts)->where('severity', 'critical')->all();
        if (! empty($critical)) {
            $this->notifyCritical($critical);
        }

        return self::SUCCESS;
    }

    private function checkStuckCampaigns(): array
    {
        $threshold = now()->subMinutes($this->option('queue-stuck-minutes'));
        $stuck = Campaign::where('status', 'sending')
            ->where('updated_at', '<', $threshold)
            ->count();

        if ($stuck > 0) {
            return [['severity' => 'warning', 'type' => 'stuck_campaigns', 'message' => "{$stuck} campaña(s) atascada(s) en estado 'sending' más de {$this->option('queue-stuck-minutes')} minutos.", 'count' => $stuck]];
        }

        return [];
    }

    private function checkInactiveServers(): array
    {
        $inactive = SendingServer::where('status', 'active')
            ->where('updated_at', '<', now()->subDay())
            ->count();

        if ($inactive > 0) {
            return [['severity' => 'critical', 'type' => 'inactive_servers', 'message' => "{$inactive} servidor(es) activo(s) sin actualización en >24h.", 'count' => $inactive]];
        }

        return [];
    }

    private function checkBounceRate(): array
    {
        $threshold = (float) $this->option('bounce-threshold');
        $alerts = [];

        Campaign::select(['id', 'uid', 'name', 'sent_count', 'bounce_count'])
            ->where('status', 'done')
            ->where('sent_count', '>', 100)
            ->chunkById(100, function ($campaigns) use ($threshold, &$alerts): void {
                foreach ($campaigns as $campaign) {
                    $rate = ($campaign->sent_count > 0)
                        ? ($campaign->bounce_count / $campaign->sent_count) * 100
                        : 0;
                    if ($rate > $threshold) {
                        $alerts[] = [
                            'severity' => 'warning',
                            'type' => 'high_bounce_rate',
                            'message' => "Campaña {$campaign->uid} ({$campaign->name}) tiene tasa de rebote del ".round($rate, 1).'%',
                            'campaign_uid' => $campaign->uid,
                            'rate' => round($rate, 2),
                        ];
                    }
                }
            });

        return $alerts;
    }

    private function checkDiskSpace(): array
    {
        $threshold = (int) $this->option('disk-threshold');
        $free = disk_free_space(base_path());
        $total = disk_total_space(base_path());
        if ($total === false || $free === false) {
            return [];
        }
        $usedPercent = 100 - (($free / $total) * 100);

        if ($usedPercent > $threshold) {
            return [['severity' => 'critical', 'type' => 'disk_space', 'message' => 'Espacio en disco al '.round($usedPercent, 1).'% (umbral '.$threshold.'%).', 'percent' => round($usedPercent, 2)]];
        }

        return [];
    }

    private function checkQueueLag(): array
    {
        try {
            $oldest = DB::table('jobs')
                ->where('queue', 'campaign')
                ->oldest('available_at')
                ->value('available_at');

            if ($oldest && now()->diffInMinutes(Carbon::createFromTimestamp($oldest)) > $this->option('queue-stuck-minutes')) {
                return [['severity' => 'critical', 'type' => 'queue_lag', 'message' => 'La cola campaign tiene jobs sin procesar desde hace más de '.$this->option('queue-stuck-minutes').' minutos.', 'oldest_job_at' => $oldest]];
            }
        } catch (\Throwable) {
            // driver queue no usa tabla 'jobs' o no disponible
        }

        return [];
    }

    private function notifyCritical(array $alerts): void
    {
        $channel = config('campaign.alerts.channel');
        if (! $channel) {
            return;
        }

        try {
            // Notificación simple; en producción se puede usar Slack/Teams
            Log::channel($channel)->critical('Campaign critical alerts', ['alerts' => $alerts]);
        } catch (\Throwable) {
            // Silenciar errores de notificación
        }
    }
}
