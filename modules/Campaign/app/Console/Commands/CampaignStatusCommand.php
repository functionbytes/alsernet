<?php

namespace Modules\Campaign\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\JobMonitor;
use Modules\CampaignSendingServers\Library\Exception\RateLimitExceeded;
use Modules\CampaignSendingServers\Library\InMemoryRateTracker;
use Modules\CampaignSendingServers\Models\SendingServer;

/**
 * Muestra el estado global de las campañas y la infraestructura de envío.
 *
 *   php artisan campaign:status
 */
class CampaignStatusCommand extends Command
{
    protected $signature = 'campaign:status';

    protected $description = 'Muestra el estado global de campañas, colas y servidores de envío.';

    public function handle(): int
    {
        $this->info('Estado del módulo Campaign');
        $this->line(str_repeat('=', 50));

        // Campañas activas
        $statuses = Campaign::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $this->line('Campañas:');
        foreach (Campaign::statusSelectOptions() as $opt) {
            $count = $statuses[$opt['value']] ?? 0;
            if ($count > 0 || in_array($opt['value'], ['sending', 'queued', 'scheduled', 'error'], true)) {
                $this->line(sprintf('  %-12s %d', $opt['text'], $count));
            }
        }

        // Emails pendientes (estimación por tracking logs)
        $sendingCampaigns = Campaign::whereIn('status', ['sending', 'queued'])->get();
        $pendingEmails = 0;
        foreach ($sendingCampaigns as $campaign) {
            $pendingEmails += $campaign->subscribersCount() - $campaign->deliveredCount();
        }
        $this->line(sprintf("\nEmails pendientes estimados: %d", max(0, $pendingEmails)));

        // Colas
        $this->line("\nColas:");
        $jobMonitorsQueued = JobMonitor::where('status', 'queued')->count();
        $jobMonitorsFailed = JobMonitor::where('status', 'failed')->count();
        $jobsTable = DB::table('jobs')->count();
        $failedJobsTable = DB::table('failed_jobs')->count();

        $this->line(sprintf('  JobMonitors queued:  %d', $jobMonitorsQueued));
        $this->line(sprintf('  JobMonitors failed:  %d', $jobMonitorsFailed));
        $this->line(sprintf('  jobs table:          %d', $jobsTable));
        $this->line(sprintf('  failed_jobs table:   %d', $failedJobsTable));

        // Servidores
        $this->line("\nServidores de envío:");
        $servers = SendingServer::active()->get();
        if ($servers->isEmpty()) {
            $this->warn('  No hay servidores activos.');
        } else {
            foreach ($servers as $server) {
                $tracker = $server->getRateLimitTracker();
                $limited = false;
                try {
                    $tracker->test();
                } catch (RateLimitExceeded $e) {
                    $limited = true;
                }

                $this->line(sprintf(
                    '  %-20s %s %s',
                    $server->name,
                    $limited ? '<fg=red>[RATE LIMITED]</>' : '<fg=green>[OK]</>',
                    $tracker instanceof InMemoryRateTracker ? '(cache)' : '(file)'
                ));
            }
        }

        // Redis
        $this->line("\nRedis:");
        try {
            Redis::ping();
            $this->info('  Conectado');
        } catch (\Throwable $e) {
            $this->error('  No conectado: '.$e->getMessage());
        }

        // Última ejecución de scheduled
        $lastDelivery = Campaign::whereNotNull('delivery_at')->latest('delivery_at')->first();
        if ($lastDelivery) {
            $this->line(sprintf(
                "\nÚltima campaña enviada: %s (%s)",
                $lastDelivery->uid,
                $lastDelivery->delivery_at->diffForHumans()
            ));
        }

        $this->line(str_repeat('=', 50));

        return self::SUCCESS;
    }
}
