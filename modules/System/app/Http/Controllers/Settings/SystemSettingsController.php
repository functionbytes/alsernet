<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\View\View;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Modules\Core\Models\Setting;
use Modules\System\Traits\FormatsBytes;

class SystemSettingsController extends Controller
{
    use FormatsBytes;

    /**
     * Display system backups page with tabs
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Configuración del sistema';
        $breadcrumb = 'Configuración / Sistema';
        $activeTab = $request->get('tab', 'queue');

        // Get queue configuration
        $queueSettings = $this->getQueueSettings();

        // Get websockets configuration
        $websocketsSettings = $this->getWebsocketsSettings();

        return view('system::system.index', compact(
            'pageTitle',
            'breadcrumb',
            'activeTab',
            'queueSettings',
            'websocketsSettings'
        ));
    }

    /**
     * Get queue backups
     */
    private function getQueueSettings()
    {
        return [
            'default_connection' => config('queue.default'),
            'connections' => config('queue.connections'),
            'failed_driver' => config('queue.failed.driver'),
            'failed_table' => config('queue.failed.database', 'failed_jobs'),
        ];
    }

    /**
     * Get websockets backups
     */
    private function getWebsocketsSettings()
    {
        return [
            'driver' => config('broadcasting.default'),
            'connections' => config('broadcasting.connections'),

            // Reverb backups
            'reverb_host' => Setting::get('reverb_host', config('reverb.servers.reverb.host', '0.0.0.0')),
            'reverb_port' => Setting::get('reverb_port', config('reverb.servers.reverb.port', 8080)),
            'reverb_scheme' => Setting::get('reverb_scheme', config('reverb.servers.reverb.scheme', 'http')),

            // Pusher backups
            'pusher_app_id' => Setting::get('pusher_app_id', config('broadcasting.connections.pusher.app_id', '')),
            'pusher_key' => Setting::get('pusher_key', config('broadcasting.connections.pusher.key', '')),
            'pusher_secret' => Setting::get('pusher_secret', config('broadcasting.connections.pusher.secret', '')),
            'pusher_cluster' => Setting::get('pusher_cluster', config('broadcasting.connections.pusher.options.cluster', 'mt1')),

            // Redis backups
            'redis_host' => Setting::get('redis_host', config('database.redis.default.host', '127.0.0.1')),
            'redis_port' => Setting::get('redis_port', config('database.redis.default.port', 6379)),
            'redis_password' => Setting::get('redis_password', config('database.redis.default.password', '')),
            'redis_database' => Setting::get('redis_database', config('database.redis.default.database', 0)),
        ];
    }

    /**
     * Update queue backups
     */
    public function updateQueue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_connection' => 'required|string',
        ]);

        try {
            Setting::set('queue_connection', $validated['default_connection']);

            // Update environment variable
            write_env('QUEUE_CONNECTION', $validated['default_connection']);

            return response()->json([
                'success' => true,
                'message' => 'Configuración de cola actualizada correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Queue settings update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración.',
            ], 500);
        }
    }

    /**
     * Update websockets backups
     */
    public function updateWebsockets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'broadcast_driver' => 'required|string',

            // Reverb
            'reverb_host' => 'nullable|string',
            'reverb_port' => 'nullable|integer',
            'reverb_scheme' => 'nullable|string',

            // Pusher
            'pusher_app_id' => 'nullable|string',
            'pusher_key' => 'nullable|string',
            'pusher_secret' => 'nullable|string',
            'pusher_cluster' => 'nullable|string',

            // Redis
            'redis_host' => 'nullable|string',
            'redis_port' => 'nullable|integer',
            'redis_password' => 'nullable|string',
            'redis_database' => 'nullable|integer',
        ]);

        try {
            Setting::set('broadcast_driver', $validated['broadcast_driver']);
            write_env('BROADCAST_DRIVER', $validated['broadcast_driver']);

            // Reverb backups
            if (isset($validated['reverb_host'])) {
                Setting::set('reverb_host', $validated['reverb_host']);
                write_env('REVERB_HOST', $validated['reverb_host']);
            }

            if (isset($validated['reverb_port'])) {
                Setting::set('reverb_port', $validated['reverb_port']);
                write_env('REVERB_PORT', $validated['reverb_port']);
            }

            if (isset($validated['reverb_scheme'])) {
                Setting::set('reverb_scheme', $validated['reverb_scheme']);
                write_env('REVERB_SCHEME', $validated['reverb_scheme']);
            }

            // Pusher backups
            if (isset($validated['pusher_app_id'])) {
                Setting::set('pusher_app_id', $validated['pusher_app_id']);
                write_env('PUSHER_APP_ID', $validated['pusher_app_id']);
            }

            if (isset($validated['pusher_key'])) {
                Setting::set('pusher_key', $validated['pusher_key']);
                write_env('PUSHER_APP_KEY', $validated['pusher_key']);
            }

            if (isset($validated['pusher_secret'])) {
                Setting::set('pusher_secret', $validated['pusher_secret']);
                write_env('PUSHER_APP_SECRET', $validated['pusher_secret']);
            }

            if (isset($validated['pusher_cluster'])) {
                Setting::set('pusher_cluster', $validated['pusher_cluster']);
                write_env('PUSHER_APP_CLUSTER', $validated['pusher_cluster']);
            }

            // Redis backups
            if (isset($validated['redis_host'])) {
                Setting::set('redis_host', $validated['redis_host']);
                write_env('REDIS_HOST', $validated['redis_host']);
            }

            if (isset($validated['redis_port'])) {
                Setting::set('redis_port', $validated['redis_port']);
                write_env('REDIS_PORT', $validated['redis_port']);
            }

            if (isset($validated['redis_password'])) {
                Setting::set('redis_password', $validated['redis_password']);
                write_env('REDIS_PASSWORD', $validated['redis_password']);
            }

            if (isset($validated['redis_database'])) {
                Setting::set('redis_database', $validated['redis_database']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuración de websockets actualizada correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Websockets settings update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración.',
            ], 500);
        }
    }

    /**
     * Test queue connection
     */
    public function testQueue(Request $request): JsonResponse
    {
        try {
            $connection = $request->input('connection', config('queue.default'));

            // Try to push a test job
            Queue::connection($connection)->pushRaw('test-payload');

            return response()->json([
                'success' => true,
                'message' => 'Conexión de cola funcionando correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Queue connection test failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la conexión.',
            ], 500);
        }
    }

    /**
     * Restart queue workers
     */
    public function restartQueue(): JsonResponse
    {
        try {
            Artisan::call('queue:restart');

            return response()->json([
                'success' => true,
                'message' => 'Workers de cola reiniciados correctamente',
            ]);
        } catch (\Exception $e) {
            Log::error('Queue workers restart failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar workers.',
            ], 500);
        }
    }

    /**
     * Get queue health statistics
     */
    public function queueStats(): JsonResponse
    {
        $stats = Cache::remember('system:queue_stats', 10, function () {
            $data = [];

            try {
                $failedStats = DB::table('failed_jobs')
                    ->selectRaw('COUNT(*) as total')
                    ->first();

                $data['pending'] = (int) DB::table('jobs')->count();
                $data['failed'] = (int) $failedStats->total;

                $data['by_queue'] = DB::table('jobs')
                    ->select('queue', DB::raw('COUNT(*) as count'))
                    ->groupBy('queue')
                    ->get();

                $data['recent_failed'] = DB::table('failed_jobs')
                    ->latest('failed_at')
                    ->limit(5)
                    ->get(['id', 'uuid', 'queue', 'exception', 'failed_at'])
                    ->map(function (object $job): object {
                        $job->exception_summary = strtok((string) $job->exception, "\n");
                        unset($job->exception);

                        return $job;
                    });
            } catch (\Throwable $e) {
                Log::error('Queue stats unavailable', ['error' => $e->getMessage()]);
                $data = ['error' => 'Las estadísticas de cola no están disponibles.'];
            }

            try {
                if (class_exists(MasterSupervisorRepository::class)) {
                    $supervisors = app(MasterSupervisorRepository::class)->all();
                    $data['horizon'] = ['status' => $supervisors ? 'running' : 'stopped'];
                }
            } catch (\Throwable) {
            }

            return $data;
        });

        return response()->json($stats);
    }

    /**
     * Delete a failed job by ID
     */
    public function deleteFailedJob(int $id): JsonResponse
    {
        try {
            $deleted = DB::table('failed_jobs')->where('id', $id)->delete();

            if (! $deleted) {
                return response()->json(['success' => false, 'message' => 'Job no encontrado'], 404);
            }

            return response()->json(['success' => true, 'message' => 'Job eliminado correctamente']);
        } catch (\Throwable $e) {
            Log::error('Failed job deletion failed', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'message' => 'Error al realizar la operación.'], 500);
        }
    }

    /**
     * Retry a failed job by ID
     */
    public function retryFailedJob(int $id): JsonResponse
    {
        try {
            $job = DB::table('failed_jobs')->where('id', $id)->first(['uuid']);

            if (! $job) {
                return response()->json(['success' => false, 'message' => 'Job no encontrado'], 404);
            }

            Artisan::call('queue:retry', ['id' => [$job->uuid]]);

            return response()->json(['success' => true, 'message' => 'Job enviado a reintentar']);
        } catch (\Throwable $e) {
            Log::error('Failed job retry failed', ['error' => $e->getMessage(), 'id' => $id]);

            return response()->json(['success' => false, 'message' => 'Error al realizar la operación.'], 500);
        }
    }

    /**
     * Get disk space statistics
     */
    public function diskStats(): JsonResponse
    {
        $storagePath = storage_path();
        $total = disk_total_space($storagePath);
        $free = disk_free_space($storagePath);
        $used = $total - $free;

        $logPath = storage_path('logs/laravel.log');
        $logSize = file_exists($logPath) ? filesize($logPath) : 0;

        return response()->json([
            'total' => $this->formatBytes((int) $total),
            'free' => $this->formatBytes((int) $free),
            'used' => $this->formatBytes((int) $used),
            'used_percent' => round($used / $total * 100, 1),
            'log_size' => $this->formatBytes((int) $logSize),
        ]);
    }
}
