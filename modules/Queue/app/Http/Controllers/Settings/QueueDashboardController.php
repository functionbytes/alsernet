<?php

namespace Modules\Queue\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueDashboardController extends Controller
{
    public function index(): View
    {
        $horizonStatus = $this->resolveHorizonStatus();
        $knownQueues = config('queue_module.known_queues', []);

        return view('queue::settings.dashboard.index', compact('horizonStatus', 'knownQueues'));
    }

    public function failedJobs(Request $request): JsonResponse
    {
        try {
            $query = DB::table('failed_jobs');

            if ($queue = $request->input('queue')) {
                $query->where('queue', $queue);
            }

            if ($dateFrom = $request->input('date_from')) {
                $query->where('failed_at', '>=', $dateFrom);
            }

            if ($dateTo = $request->input('date_to')) {
                $query->where('failed_at', '<=', $dateTo);
            }

            $total = $query->count();
            $skip = (int) $request->input('skip', 0);
            $take = (int) $request->input('take', 20);

            $jobs = $query->latest('failed_at')->skip($skip)->take($take)->get()
                ->map(fn ($job) => $this->formatFailedJob($job));

            return response()->json(['data' => $jobs, 'totalCount' => $total]);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to load failed jobs', ['error' => $e->getMessage()]);

            return response()->json(['data' => [], 'totalCount' => 0]);
        }
    }

    public function pendingJobs(Request $request): JsonResponse
    {
        try {
            $query = DB::table('jobs');

            if ($queue = $request->input('queue')) {
                $query->where('queue', $queue);
            }

            $total = $query->count();
            $skip = (int) $request->input('skip', 0);
            $take = (int) $request->input('take', 20);

            $jobs = $query->orderBy('created_at')->skip($skip)->take($take)->get()
                ->map(fn ($job) => $this->formatPendingJob($job));

            return response()->json(['data' => $jobs, 'totalCount' => $total]);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to load pending jobs', ['error' => $e->getMessage()]);

            return response()->json(['data' => [], 'totalCount' => 0]);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $failed24h = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
            $failed7d = DB::table('failed_jobs')->where('failed_at', '>=', now()->subWeek())->count();
            $failedTotal = DB::table('failed_jobs')->count();

            $topFailing = DB::table('failed_jobs')
                ->latest('failed_at')
                ->limit(200)
                ->pluck('payload')
                ->map(fn ($payload) => $this->extractJobClass($payload))
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(5)
                ->map(fn ($count, $class) => ['class' => $class, 'count' => $count])
                ->values();

            $pendingTotal = 0;
            $queuePending = [];

            try {
                $knownQueues = config('queue_module.known_queues', []);
                foreach ($knownQueues as $queue) {
                    $count = DB::table('jobs')->where('queue', $queue)->count();
                    $queuePending[$queue] = $count;
                    $pendingTotal += $count;
                }
            } catch (\Throwable) {
                // jobs table may not exist (Redis-only setup)
            }

            return response()->json([
                'failed_24h' => $failed24h,
                'failed_7d' => $failed7d,
                'failed_total' => $failedTotal,
                'pending_total' => $pendingTotal,
                'queue_pending' => $queuePending,
                'top_failing' => $topFailing,
                'horizon_status' => $this->resolveHorizonStatus(),
            ]);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to load stats', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to load stats'], 500);
        }
    }

    public function retryJob(Request $request, string $uuid): JsonResponse
    {
        try {
            $exists = DB::table('failed_jobs')->where('uuid', $uuid)->exists();

            if (! $exists) {
                return response()->json(['error' => 'Job not found'], 404);
            }

            Artisan::call('queue:retry', ['id' => [$uuid]]);

            return response()->json(['message' => 'Job queued for retry']);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to retry job', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to retry job'], 500);
        }
    }

    public function deleteFailedJob(Request $request, string $uuid): JsonResponse
    {
        try {
            $deleted = DB::table('failed_jobs')->where('uuid', $uuid)->delete();

            if (! $deleted) {
                return response()->json(['error' => 'Job not found'], 404);
            }

            return response()->json(['message' => 'Job deleted']);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to delete job', ['uuid' => $uuid, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to delete job'], 500);
        }
    }

    public function bulkRetry(Request $request): JsonResponse
    {
        $uuids = $request->input('uuids', []);

        if (empty($uuids)) {
            return response()->json(['error' => 'No UUIDs provided'], 422);
        }

        try {
            Artisan::call('queue:retry', ['id' => $uuids]);

            return response()->json(['message' => count($uuids).' trabajos enviados a reintentar']);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to bulk retry jobs', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to retry jobs'], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $uuids = $request->input('uuids', []);

        if (empty($uuids)) {
            return response()->json(['error' => 'No UUIDs provided'], 422);
        }

        try {
            $deleted = DB::table('failed_jobs')->whereIn('uuid', $uuids)->delete();

            return response()->json(['message' => $deleted.' trabajos eliminados']);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to bulk delete jobs', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to delete jobs'], 500);
        }
    }

    public function retryAll(Request $request): JsonResponse
    {
        try {
            $options = [];

            if ($queue = $request->input('queue')) {
                $options['--queue'] = $queue;
            }

            Artisan::call('queue:retry-all', $options);

            $output = Artisan::output();

            return response()->json(['message' => trim($output)]);
        } catch (\Throwable $e) {
            Log::error('QueueDashboard: failed to retry all jobs', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to retry all jobs'], 500);
        }
    }

    private function resolveHorizonStatus(): string
    {
        try {
            Artisan::call('horizon:status');

            return trim(Artisan::output()) ?: 'unknown';
        } catch (\Throwable) {
            return 'unknown';
        }
    }

    private function extractJobClass(string $payload): ?string
    {
        $decoded = json_decode($payload, true);

        return $decoded['displayName'] ?? $decoded['job'] ?? null;
    }

    private function formatFailedJob(object $job): array
    {
        return [
            'uuid' => $job->uuid,
            'queue' => $job->queue,
            'job_class' => $this->extractJobClass($job->payload),
            'failed_at' => $job->failed_at,
            'exception' => $job->exception ?? null,
        ];
    }

    private function formatPendingJob(object $job): array
    {
        $payload = json_decode($job->payload, true);

        return [
            'id' => $job->id,
            'queue' => $job->queue,
            'job_class' => $payload['displayName'] ?? $payload['job'] ?? null,
            'attempts' => $job->attempts,
            'created_at' => $job->created_at ?? null,
            'available_at' => $job->available_at ?? null,
        ];
    }
}
