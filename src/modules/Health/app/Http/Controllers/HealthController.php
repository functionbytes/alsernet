<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;
use Spatie\Health\Facades\Health;
use Spatie\Health\Models\HealthCheckResultHistoryItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;

class HealthController extends Controller
{
    /**
     * Display health check dashboard
     */
    public function index(): View
    {
        $checkResults = Health::registeredChecks();

        $results = collect($checkResults)->map(function ($check) {
            $result = $check->run();

            // Create a simple object with all the data we need
            return (object) [
                'check' => $check,
                'status' => $result->status,
                'label' => $check->getLabel(),
                'shortSummary' => $result->shortSummary,
                'notificationMessage' => $result->notificationMessage,
                'meta' => $result->meta,
            ];
        });

        $pageTitle = 'System Health Check';
        $breadcrumb = 'Settings / Health Check';

        // Calculate overall status
        $overallStatus = 'ok';
        if ($results->contains(fn ($result) => $result->status->value === 'failed')) {
            $overallStatus = 'failed';
        } elseif ($results->contains(fn ($result) => $result->status->value === 'warning')) {
            $overallStatus = 'warning';
        }

        // Get history from last 24 hours
        $history = collect();

        return view('health::settings.index', compact(
            'results',
            'overallStatus',
            'history',
            'pageTitle',
            'breadcrumb'
        ));
    }

    /**
     * Run health checks and return JSON
     */
    public function check(): JsonResponse
    {
        $checkResults = Health::registeredChecks();

        $results = collect($checkResults)->map(function ($check) {
            $result = $check->run();
            $result->check ??= $check;

            return [
                'name' => $result->check->getName(),
                'label' => $result->check->getLabel(),
                'status' => $result->status->value,
                'short_summary' => $result->shortSummary,
                'notification_message' => $result->notificationMessage,
                'meta' => $result->meta,
            ];
        });

        $overallStatus = 'ok';
        if ($results->contains(fn ($result) => $result['status'] === 'failed')) {
            $overallStatus = 'failed';
        } elseif ($results->contains(fn ($result) => $result['status'] === 'warning')) {
            $overallStatus = 'warning';
        }

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'checks' => $results,
        ]);
    }

    /**
     * Get historical health check data
     */
    public function history(Request $request): View|JsonResponse
    {
        $days = $request->input('days', 7);

        // Get history from database
        $history = HealthCheckResultHistoryItem::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit(1000)
            ->get()
            ->groupBy('check_name')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'status' => $item->status,
                        'created_at' => $item->created_at->toIso8601String(),
                        'short_summary' => $item->short_summary,
                    ];
                });
            });

        // If request wants JSON (AJAX call), return JSON
        if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'period_days' => $days,
                'history' => $history,
            ]);
        }

        // Otherwise return the view
        return view('health::settings.history', [
            'pageTitle' => 'Historial de verificaciones',
        ]);
    }

    /**
     * Simple health check endpoint (no authentication)
     * Used by load balancers, monitoring systems, etc.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Full health check endpoint for external monitoring.
     * Without a valid token, returns only overall status to avoid leaking internal details.
     */
    public function health(): JsonResponse
    {
        try {
            $payload = Cache::remember('health_check_results', 60, function () {
                $checkResults = Health::registeredChecks();

                $results = collect($checkResults)->map(function ($check) {
                    $result = $check->run();

                    return [
                        'name' => $check->getName(),
                        'label' => $check->getLabel(),
                        'status' => $result->status->value,
                        'short_summary' => $result->shortSummary,
                    ];
                });

                $overallStatus = 'ok';
                if ($results->contains(fn ($result) => $result['status'] === 'failed')) {
                    $overallStatus = 'failed';
                } elseif ($results->contains(fn ($result) => $result['status'] === 'warning')) {
                    $overallStatus = 'warning';
                }

                return ['status' => $overallStatus, 'checks' => $results];
            });

            if (! $this->hasValidHealthToken()) {
                return response()->json([
                    'status' => $payload['status'],
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            return response()->json([
                'status' => $payload['status'],
                'timestamp' => now()->toIso8601String(),
                'checks' => $payload['checks'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    /**
     * Detailed health check with system information
     * Only in debug mode for security
     */
    public function detailed(): JsonResponse
    {
        abort_unless($this->hasValidHealthToken(), 403);

        try {

            $checkResults = Health::registeredChecks();

            $results = collect($checkResults)->map(function ($check) {
                $result = $check->run();

                return [
                    'name' => $check->getName(),
                    'label' => $check->getLabel(),
                    'status' => $result->status->value,
                    'short_summary' => $result->shortSummary,
                    'notification_message' => $result->notificationMessage,
                    'meta' => $result->meta,
                ];
            });

            $overallStatus = 'ok';
            if ($results->contains(fn ($result) => $result['status'] === 'failed')) {
                $overallStatus = 'failed';
            } elseif ($results->contains(fn ($result) => $result['status'] === 'warning')) {
                $overallStatus = 'warning';
            }

            // System information — intentionally omits version/env details to limit fingerprinting
            $systemInfo = [
                'app_name' => config('app.name'),
                'server_time' => now()->toIso8601String(),
                'uptime_seconds' => $this->getServerUptime(),
            ];

            return response()->json([
                'status' => $overallStatus,
                'timestamp' => now()->toIso8601String(),
                'system' => $systemInfo,
                'checks' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Detailed health check error',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    /**
     * Check if valid health token provided.
     * Accepts Bearer token in Authorization header (preferred) or query string (legacy).
     */
    private function hasValidHealthToken(): bool
    {
        $validToken = config('healthcheck.api_token');

        if (! $validToken) {
            return false;
        }

        $authHeader = request()->header('Authorization', '');
        if (str_starts_with($authHeader, 'Bearer ')) {
            return hash_equals($validToken, substr($authHeader, 7));
        }

        $token = request()->query('token');

        return $token && hash_equals($validToken, $token);
    }

    /**
     * Get server uptime in seconds
     */
    private function getServerUptime(): int
    {
        try {
            $process = new Process(['cat', '/proc/uptime']);
            $process->run();

            if ($process->isSuccessful() && $process->getOutput()) {
                return (int) explode(' ', $process->getOutput())[0];
            }
        } catch (\Exception $e) {
            // Not all servers have /proc/uptime
        }

        return 0;
    }

    /**
     * Execute scheduler manually
     */
    public function runSchedule(Request $request): JsonResponse
    {
        try {
            // Run schedule command
            Artisan::call('schedule:run');
            $output = Artisan::output();

            // Set schedule check heartbeat to mark it as running
            Artisan::call('health:schedule-check-heartbeat');

            // Also dispatch queue check jobs if queue is configured
            if (config('queue.default') !== 'sync') {
                Artisan::call('health:queue-check-heartbeat');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Scheduler ejecutado exitosamente',
                'output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al ejecutar el scheduler. Por favor, inténtalo de nuevo.',
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get queue worker status
     */
    public function queueStatus(): JsonResponse
    {
        try {
            $queueConnection = config('queue.default');

            // Check if workers are running
            $workersRunning = false;
            $workerCount = 0;

            $process = new Process(['ps', 'aux']);
            $process->run();

            if ($process->isSuccessful()) {
                $lines = explode("\n", $process->getOutput());
                $output = array_filter($lines, function (string $line): bool {
                    return (stripos($line, 'queue:work') !== false || stripos($line, 'horizon') !== false)
                        && stripos($line, 'grep') === false;
                });
                $workerCount = count($output);
                $workersRunning = $workerCount > 0;
            }

            // Get queue size
            $queueSize = 0;
            if ($queueConnection === 'database') {
                $queueSize = DB::table(config('queue.connections.database.table', 'jobs'))->count();
            } elseif ($queueConnection === 'redis') {
                try {
                    $redis = Redis::connection(config('queue.connections.redis.connection', 'default'));
                    $queueSize = $redis->llen('queues:'.config('queue.connections.redis.queue', 'default'));
                } catch (\Exception $e) {
                    $queueSize = 'N/A';
                }
            }

            // Get failed jobs count
            $failedJobsCount = DB::table(config('queue.failed.table', 'failed_jobs'))->count();

            return response()->json([
                'status' => 'success',
                'connection' => $queueConnection,
                'workers_running' => $workersRunning,
                'worker_count' => $workerCount,
                'pending_jobs' => $queueSize,
                'failed_jobs' => $failedJobsCount,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get queue status. Please try again.',
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Process pending queue jobs
     */
    public function processQueue(Request $request): JsonResponse
    {
        try {
            $queueConnection = config('queue.default');

            if ($queueConnection === 'sync') {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Queue is set to sync mode. Jobs are processed immediately.',
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            // Process a limited number of jobs
            Artisan::call('queue:work', [
                '--once' => true,
                '--tries' => 3,
            ]);

            $output = Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'Queue jobs processed',
                'output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process queue. Please try again.',
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get scheduled tasks list
     */
    public function scheduleList(): JsonResponse
    {
        try {
            Artisan::call('schedule:list');
            $output = Artisan::output();

            // Parse schedule list output
            $lines = explode("\n", trim($output));
            $tasks = [];

            foreach ($lines as $line) {
                if (preg_match('/^(.+?)\s+Next Due:\s+(.+)$/', trim($line), $matches)) {
                    $tasks[] = [
                        'command' => trim($matches[1]),
                        'next_due' => trim($matches[2]),
                    ];
                }
            }

            return response()->json([
                'status' => 'success',
                'tasks' => $tasks,
                'raw_output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get schedule list. Please try again.',
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Generate Supervisor configuration
     */
    public function generateSupervisorConfig(Request $request): JsonResponse
    {
        $request->validate([
            'workers' => 'integer|min:1|max:20',
            'tries' => 'integer|min:1|max:10',
            'timeout' => 'integer|min:30|max:3600',
        ]);

        try {
            $workers = $request->input('workers', 3);
            $tries = $request->input('tries', 3);
            $timeout = $request->input('timeout', 300);

            // Run the artisan command
            Artisan::call('health:supervisor-config', [
                '--workers' => $workers,
                '--tries' => $tries,
                '--timeout' => $timeout,
                '--force' => true,
            ]);

            $output = Artisan::output();

            // Get the generated file path (dentro del módulo Health)
            $appName = str_replace(' ', '-', strtolower(config('app.name', 'laravel')));
            $configPath = base_path('modules/Health/storage/supervisor');
            $configFile = "{$configPath}/{$appName}-worker.conf";

            // Read the generated config
            $configContent = file_exists($configFile) ? file_get_contents($configFile) : null;

            return response()->json([
                'status' => 'success',
                'message' => 'Configuración de Supervisor generada exitosamente',
                'config_file' => $configFile,
                'config_content' => $configContent,
                'app_name' => $appName,
                'instructions' => $this->getSupervisorInstructions($appName, $configFile),
                'output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al generar configuración. Por favor, inténtalo de nuevo.',
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Download Supervisor configuration file
     */
    public function downloadSupervisorConfig(): BinaryFileResponse|JsonResponse
    {
        try {
            $appName = str_replace(' ', '-', strtolower(config('app.name', 'laravel')));
            $configFile = base_path("modules/Health/storage/supervisor/{$appName}-worker.conf");

            if (! file_exists($configFile)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Archivo de configuración no encontrado. Genera primero la configuración.',
                ], 404);
            }

            return response()->download($configFile, "{$appName}-worker.conf", [
                'Content-Type' => 'text/plain',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al descargar configuración. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Delete a single health history record
     */
    public function destroyHistoryRecord(int $id): RedirectResponse
    {
        HealthCheckResultHistoryItem::findOrFail($id)->delete();

        return redirect()->route('settings.health.history')->with('success', 'Registro eliminado correctamente.');
    }

    /**
     * Bulk delete health history records
     */
    public function bulkDestroyHistory(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $count = HealthCheckResultHistoryItem::whereIn('id', $request->input('ids'))->delete();

        return response()->json(['count' => $count]);
    }

    /**
     * Get Supervisor installation instructions
     */
    private function getSupervisorInstructions(string $appName, string $configFile): array
    {
        return [
            'install' => [
                'ubuntu' => 'sudo apt-get install supervisor',
                'macos' => 'brew install supervisor && brew services start supervisor',
            ],
            'setup' => [
                "sudo cp {$configFile} /etc/supervisor/conf.d/{$appName}-worker.conf",
                'sudo supervisorctl reread',
                'sudo supervisorctl update',
                "sudo supervisorctl start {$appName}-worker:*",
            ],
            'verify' => 'sudo supervisorctl status',
            'useful_commands' => [
                'status' => 'sudo supervisorctl status',
                'restart' => "sudo supervisorctl restart {$appName}-worker:*",
                'stop' => "sudo supervisorctl stop {$appName}-worker:*",
                'logs' => "sudo supervisorctl tail -f {$appName}-worker:* stdout",
            ],
        ];
    }
}
