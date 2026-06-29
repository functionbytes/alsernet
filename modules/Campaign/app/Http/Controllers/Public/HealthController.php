<?php

namespace Modules\Campaign\Http\Controllers\Public;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\JobMonitor;
use Modules\CampaignSendingServers\Models\SendingServer;

/**
 * Health check del módulo Campaign para monitoring.
 *
 *   GET /campaign/health        → 200 con JSON detallado
 *   GET /campaign/health?simple → 200/503 plain text "ok"/"down"
 *
 * Comprueba: BD ok, schedule corriendo, workers ok, sending servers
 * configurados, backlog en cola.
 */
class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'tables' => $this->checkTables(),
            'redis' => $this->checkRedis(),
            'disk_space' => $this->checkDiskSpace(),
            'sending_servers' => $this->checkSendingServers(),
            'queue_backlog' => $this->checkQueueBacklog(),
            'campaigns_active' => $this->countActiveCampaigns(),
            'last_executed_scheduled' => $this->lastScheduledExecution(),
            'cron_scheduled' => $this->checkCronScheduled(),
        ];

        $allOk = collect($checks)->every(fn ($c) => ($c['ok'] ?? true) !== false);

        return response()->json([
            'ok' => $allOk,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allOk ? 200 : 503);
    }

    public function simple(): Response
    {
        $ok = $this->checkDatabase()['ok'];

        return response($ok ? 'ok' : 'down', $ok ? 200 : 503, ['Content-Type' => 'text/plain']);
    }

    protected function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');

            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function checkTables(): array
    {
        $required = ['campaigns', 'campaign_maillists', 'campaign_subscribers', 'campaign_tracking_logs', 'campaign_sending_servers'];
        $missing = collect($required)->reject(fn ($t) => Schema::hasTable($t))->values()->all();

        return ['ok' => empty($missing), 'missing' => $missing];
    }

    protected function checkSendingServers(): array
    {
        $total = SendingServer::count();
        $active = SendingServer::active()->count();

        return [
            'ok' => $active > 0,
            'total' => $total,
            'active' => $active,
            'message' => $active === 0 ? 'No hay sending servers activos' : null,
        ];
    }

    protected function checkQueueBacklog(): array
    {
        try {
            $jobMonitorBacklog = JobMonitor::where('status', 'queued')->count();
            $jobsTable = Schema::hasTable('jobs') ? DB::table('jobs')->count() : null;
            $failedTable = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;

            return [
                'ok' => $jobMonitorBacklog < 1000 && ($failedTable === null || $failedTable < 100),
                'job_monitors_queued' => $jobMonitorBacklog,
                'jobs_pending' => $jobsTable,
                'jobs_failed' => $failedTable,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function countActiveCampaigns(): array
    {
        return [
            'sending' => Campaign::where('status', 'sending')->count(),
            'queued' => Campaign::where('status', 'queued')->count(),
            'scheduled' => Campaign::where('status', 'scheduled')->count(),
            'lists' => CampaignMaillist::count(),
        ];
    }

    protected function lastScheduledExecution(): array
    {
        $last = Campaign::whereNotNull('delivery_at')->latest('delivery_at')->first();

        return [
            'last_delivery_at' => optional($last)->delivery_at?->toIso8601String(),
            'last_campaign_uid' => optional($last)->uid,
        ];
    }

    protected function checkRedis(): array
    {
        try {
            Redis::ping();

            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function checkDiskSpace(): array
    {
        $free = disk_free_space(storage_path());
        $total = disk_total_space(storage_path());
        $percentFree = $total > 0 ? ($free / $total) * 100 : 0;

        return [
            'ok' => $percentFree > 10,
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'percent_free' => round($percentFree, 2),
        ];
    }

    protected function checkCronScheduled(): array
    {
        $lastRun = \DB::table('cache')
            ->where('key', 'like', '%campaign:execute_scheduled%')
            ->orWhere('key', 'like', '%campaign_execute_scheduled%')
            ->first();

        // Fallback: si hay campañas scheduled pero no se han ejecutado recientemente
        $scheduledCount = Campaign::where('status', 'scheduled')->where('run_at', '<', now()->subMinutes(5))->count();

        return [
            'ok' => $scheduledCount === 0,
            'stale_scheduled_campaigns' => $scheduledCount,
            'hint' => $scheduledCount > 0 ? 'Verifica que el cron campaign:execute-scheduled esté corriendo cada minuto.' : null,
        ];
    }
}
