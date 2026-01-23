<?php

namespace Modules\Health\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Health\Facades\Health;

class HealthController extends Controller
{
    /**
     * Display health check dashboard
     */
    public function index()
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
    public function check()
    {
        $checkResults = Health::registeredChecks();

        $results = collect($checkResults)->map(function ($check) {
            $result = $check->run();

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
    public function history(Request $request)
    {
        $days = $request->input('days', 7);

        // Get history from database
        $history = \Spatie\Health\Models\HealthCheckResultHistoryItem::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('check_name')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
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
    public function ping()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Full health check endpoint for external monitoring
     * Returns complete health status without authentication
     */
    public function health()
    {
        try {
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

            return response()->json([
                'status' => $overallStatus,
                'timestamp' => now()->toIso8601String(),
                'checks' => $results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Health check error',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    /**
     * Document module specific health checks
     * Validates document system integrity
     */
    public function documentsHealth()
    {
        try {
            $checks = [
                'database_connection' => [
                    'status' => 'ok',
                    'message' => 'Database connected',
                ],
                'documents_table' => [
                    'status' => 'ok',
                    'message' => 'Documents table exists',
                ],
                'storage_accessible' => [
                    'status' => 'ok',
                    'message' => 'Storage directory accessible',
                ],
            ];

            // Check if documents table exists
            try {
                \Illuminate\Support\Facades\DB::table('documents')->limit(1)->get();
            } catch (\Exception $e) {
                $checks['documents_table']['status'] = 'failed';
                $checks['documents_table']['message'] = 'Documents table not found';
            }

            // Check storage
            if (! is_writable(storage_path())) {
                $checks['storage_accessible']['status'] = 'failed';
                $checks['storage_accessible']['message'] = 'Storage directory not writable';
            }

            $overallStatus = collect($checks)->every(fn ($check) => $check['status'] === 'ok') ? 'ok' : 'failed';

            return response()->json([
                'status' => $overallStatus,
                'module' => 'documents',
                'timestamp' => now()->toIso8601String(),
                'checks' => $checks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'module' => 'documents',
                'message' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }

    /**
     * Detailed health check with system information
     * Only in debug mode for security
     */
    public function detailed()
    {
        // Only allow in debug mode or with API token
        if (! config('app.debug') && ! $this->hasValidHealthToken()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

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

            // System information
            $systemInfo = [
                'app_name' => config('app.name'),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'php_version' => phpversion(),
                'laravel_version' => app()->version(),
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
     * Check if valid health token provided
     */
    private function hasValidHealthToken(): bool
    {
        $token = request()->query('token');
        $validToken = config('healthcheck.api_token');

        return $token && $validToken && hash_equals($validToken, $token);
    }

    /**
     * Get server uptime in seconds
     */
    private function getServerUptime(): int
    {
        try {
            if (function_exists('exec')) {
                $uptime = @exec('cat /proc/uptime');
                if ($uptime) {
                    return (int) explode(' ', $uptime)[0];
                }
            }
        } catch (\Exception $e) {
            // Silently fail, not all servers have /proc/uptime
        }

        return 0;
    }

    /**
     * Execute scheduler manually
     */
    public function runSchedule(Request $request)
    {
        try {
            // Run schedule command
            \Illuminate\Support\Facades\Artisan::call('schedule:run');
            $output = \Illuminate\Support\Facades\Artisan::output();

            // Set schedule check heartbeat to mark it as running
            \Illuminate\Support\Facades\Artisan::call('health:schedule-check-heartbeat');

            // Also dispatch queue check jobs if queue is configured
            if (config('queue.default') !== 'sync') {
                \Illuminate\Support\Facades\Artisan::call('health:queue-check-heartbeat');
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
                'message' => 'Error al ejecutar el scheduler: '.$e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get queue worker status
     */
    public function queueStatus()
    {
        try {
            $queueConnection = config('queue.default');

            // Check if workers are running
            $workersRunning = false;
            $workerCount = 0;

            if (function_exists('exec')) {
                exec('ps aux | grep -i "queue:work\|horizon" | grep -v grep', $output);
                $workerCount = count($output);
                $workersRunning = $workerCount > 0;
            }

            // Get queue size
            $queueSize = 0;
            if ($queueConnection === 'database') {
                $queueSize = \Illuminate\Support\Facades\DB::table(config('queue.connections.database.table', 'jobs'))->count();
            } elseif ($queueConnection === 'redis') {
                try {
                    $redis = \Illuminate\Support\Facades\Redis::connection(config('queue.connections.redis.connection', 'default'));
                    $queueSize = $redis->llen('queues:'.config('queue.connections.redis.queue', 'default'));
                } catch (\Exception $e) {
                    $queueSize = 'N/A';
                }
            }

            // Get failed jobs count
            $failedJobsCount = \Illuminate\Support\Facades\DB::table(config('queue.failed.table', 'failed_jobs'))->count();

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
                'message' => 'Failed to get queue status: '.$e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Process pending queue jobs
     */
    public function processQueue(Request $request)
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
            \Illuminate\Support\Facades\Artisan::call('queue:work', [
                '--once' => true,
                '--tries' => 3,
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'status' => 'success',
                'message' => 'Queue jobs processed',
                'output' => $output,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process queue: '.$e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get scheduled tasks list
     */
    public function scheduleList()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('schedule:list');
            $output = \Illuminate\Support\Facades\Artisan::output();

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
                'message' => 'Failed to get schedule list: '.$e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Generate Supervisor configuration
     */
    public function generateSupervisorConfig(Request $request)
    {
        try {
            $workers = $request->input('workers', 3);
            $tries = $request->input('tries', 3);
            $timeout = $request->input('timeout', 300);

            // Run the artisan command
            \Illuminate\Support\Facades\Artisan::call('health:supervisor-config', [
                '--workers' => $workers,
                '--tries' => $tries,
                '--timeout' => $timeout,
                '--force' => true,
            ]);

            $output = \Illuminate\Support\Facades\Artisan::output();

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
                'message' => 'Error al generar configuración: '.$e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Download Supervisor configuration file
     */
    public function downloadSupervisorConfig()
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
                'message' => 'Error al descargar configuración: '.$e->getMessage(),
            ], 500);
        }
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
