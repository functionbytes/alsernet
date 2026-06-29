<?php

namespace Modules\Campaign\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Monitorea la cola campaign y recomienda/ajusta el número de workers.
 * En producción se conecta a Supervisorctl, systemd o Kubernetes HPA.
 */
class QueueAutoscaler
{
    public function check(string $queue = 'campaign'): array
    {
        $metrics = $this->metrics($queue);
        $recommendation = 'stable';
        $recommendedWorkers = $metrics['current_workers'];

        if ($metrics['pending_jobs'] > 5000 && $metrics['avg_wait_minutes'] > 10) {
            $recommendation = 'scale_up';
            $recommendedWorkers = min(50, $metrics['current_workers'] + 5);
        } elseif ($metrics['pending_jobs'] < 100 && $metrics['avg_wait_minutes'] < 1) {
            $recommendation = 'scale_down';
            $recommendedWorkers = max(1, $metrics['current_workers'] - 2);
        }

        return [
            'queue' => $queue,
            'recommendation' => $recommendation,
            'current_workers' => $metrics['current_workers'],
            'recommended_workers' => $recommendedWorkers,
            'pending_jobs' => $metrics['pending_jobs'],
            'avg_wait_minutes' => round($metrics['avg_wait_minutes'], 2),
        ];
    }

    public function metrics(string $queue = 'campaign'): array
    {
        $pending = 0;
        $avgWait = 0.0;
        $workers = 0;

        try {
            $pending = DB::table('jobs')
                ->where('queue', $queue)
                ->count();

            $oldest = DB::table('jobs')
                ->where('queue', $queue)
                ->oldest('available_at')
                ->value('available_at');

            if ($oldest) {
                $avgWait = now()->diffInMinutes(Carbon::createFromTimestamp($oldest));
            }
        } catch (\Throwable) {
            // driver no basado en BD
        }

        // Contar procesos queue:work (aproximado)
        try {
            $result = Process::run('pgrep -f "queue:work.*'.$queue.'" | wc -l');
            $workers = (int) trim($result->output());
        } catch (\Throwable) {
            $workers = 0;
        }

        return [
            'pending_jobs' => $pending,
            'avg_wait_minutes' => $avgWait,
            'current_workers' => $workers,
        ];
    }

    public function alertIfNeeded(string $queue = 'campaign'): void
    {
        $check = $this->check($queue);
        if ($check['recommendation'] === 'scale_up') {
            Log::warning('Queue autoscaler recommends scale up', $check);
        }
    }
}
