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
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Modules\Core\Models\Setting;
use Modules\System\Providers\SystemServiceProvider;
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
            // Conexión cliente (REVERB_HOST/PORT/SCHEME), que es lo que se aplica en runtime.
            'reverb_host' => Setting::get('reverb_host', config('broadcasting.connections.reverb.options.host', '')),
            'reverb_port' => Setting::get('reverb_port', config('broadcasting.connections.reverb.options.port', 443)),
            'reverb_scheme' => Setting::get('reverb_scheme', config('broadcasting.connections.reverb.options.scheme', 'https')),

            // Pusher backups
            'pusher_app_id' => Setting::get('pusher_app_id', config('broadcasting.connections.pusher.app_id', '')),
            'pusher_key' => Setting::get('pusher_key', config('broadcasting.connections.pusher.key', '')),
            // Nunca se envía el secreto a la vista: solo si hay uno guardado.
            'pusher_secret_set' => (string) Setting::get('pusher_secret', config('broadcasting.connections.pusher.secret', '')) !== '',
            'pusher_cluster' => Setting::get('pusher_cluster', config('broadcasting.connections.pusher.options.cluster', 'mt1')),

            // Redis backups
            'redis_host' => Setting::get('redis_host', config('database.redis.default.host', '127.0.0.1')),
            'redis_port' => Setting::get('redis_port', config('database.redis.default.port', 6379)),
            'redis_password_set' => (string) Setting::get('redis_password', config('database.redis.default.password', '')) !== '',
            'redis_database' => Setting::get('redis_database', config('database.redis.default.database', 0)),
        ];
    }

    /**
     * Update queue backups
     *
     * 29-sep-2026: ya no se reescribe el .env (write_env). Se guarda en settings
     * y SystemServiceProvider::applyRuntimeSettings() lo aplica en cada petición.
     */
    public function updateQueue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_connection' => ['required', 'string', Rule::in(array_keys((array) config('queue.connections', [])))],
        ]);

        try {
            Setting::set('queue_connection', $validated['default_connection']);
            SystemServiceProvider::clearRuntimeSettingsCache();

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
     *
     * 29-sep-2026: sin write_env; se guarda en settings y se aplica en runtime
     * (broadcast/pusher/reverb). Las contraseñas vacías = sin cambios (el
     * formulario ya no las rellena) y se guardan cifradas (Setting::SECRET_KEYS).
     * Redis NO se aplica en runtime: los propios settings se leen de la caché
     * Redis, así que cambiar su conexión desde ellos es circular.
     */
    public function updateWebsockets(Request $request): JsonResponse
    {
        $plain = ['nullable', 'string', 'max:255', 'regex:/^[^\r\n\0]*$/'];

        $validated = $request->validate([
            'broadcast_driver' => ['required', 'string', Rule::in(array_keys((array) config('broadcasting.connections', [])))],

            // Reverb
            'reverb_host' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-]+$/'],
            'reverb_port' => 'nullable|integer|min:1|max:65535',
            'reverb_scheme' => 'nullable|in:http,https',

            // Pusher
            'pusher_app_id' => $plain,
            'pusher_key' => $plain,
            'pusher_secret' => $plain,
            'pusher_cluster' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-]+$/'],

            // Redis
            'redis_host' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-:]+$/'],
            'redis_port' => 'nullable|integer|min:1|max:65535',
            'redis_password' => $plain,
            'redis_database' => 'nullable|integer|min:0|max:64',
        ]);

        try {
            Setting::set('broadcast_driver', $validated['broadcast_driver']);

            foreach ([
                'reverb_host', 'reverb_port', 'reverb_scheme',
                'pusher_app_id', 'pusher_key', 'pusher_secret', 'pusher_cluster',
                'redis_host', 'redis_port', 'redis_password', 'redis_database',
            ] as $key) {
                if (isset($validated[$key]) && $validated[$key] !== '') {
                    Setting::set($key, (string) $validated[$key]);
                }
            }

            SystemServiceProvider::clearRuntimeSettingsCache();

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
