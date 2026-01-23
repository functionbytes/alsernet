<?php

namespace Modules\System\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Modules\Core\Models\Setting;

class SystemSettingsController extends Controller
{
    /**
     * Display system backups page with tabs
     */
    public function index(Request $request)
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
    public function updateQueue(Request $request)
    {
        $validated = $request->validate([
            'default_connection' => 'required|string',
        ]);

        try {
            Setting::set('queue_connection', $validated['default_connection']);

            // Update environment variable
            $this->updateEnvVariable('QUEUE_CONNECTION', $validated['default_connection']);

            return response()->json([
                'success' => true,
                'message' => 'Configuración de cola actualizada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update websockets backups
     */
    public function updateWebsockets(Request $request)
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
            $this->updateEnvVariable('BROADCAST_DRIVER', $validated['broadcast_driver']);

            // Reverb backups
            if (isset($validated['reverb_host'])) {
                Setting::set('reverb_host', $validated['reverb_host']);
                $this->updateEnvVariable('REVERB_HOST', $validated['reverb_host']);
            }

            if (isset($validated['reverb_port'])) {
                Setting::set('reverb_port', $validated['reverb_port']);
                $this->updateEnvVariable('REVERB_PORT', $validated['reverb_port']);
            }

            if (isset($validated['reverb_scheme'])) {
                Setting::set('reverb_scheme', $validated['reverb_scheme']);
                $this->updateEnvVariable('REVERB_SCHEME', $validated['reverb_scheme']);
            }

            // Pusher backups
            if (isset($validated['pusher_app_id'])) {
                Setting::set('pusher_app_id', $validated['pusher_app_id']);
                $this->updateEnvVariable('PUSHER_APP_ID', $validated['pusher_app_id']);
            }

            if (isset($validated['pusher_key'])) {
                Setting::set('pusher_key', $validated['pusher_key']);
                $this->updateEnvVariable('PUSHER_APP_KEY', $validated['pusher_key']);
            }

            if (isset($validated['pusher_secret'])) {
                Setting::set('pusher_secret', $validated['pusher_secret']);
                $this->updateEnvVariable('PUSHER_APP_SECRET', $validated['pusher_secret']);
            }

            if (isset($validated['pusher_cluster'])) {
                Setting::set('pusher_cluster', $validated['pusher_cluster']);
                $this->updateEnvVariable('PUSHER_APP_CLUSTER', $validated['pusher_cluster']);
            }

            // Redis backups
            if (isset($validated['redis_host'])) {
                Setting::set('redis_host', $validated['redis_host']);
                $this->updateEnvVariable('REDIS_HOST', $validated['redis_host']);
            }

            if (isset($validated['redis_port'])) {
                Setting::set('redis_port', $validated['redis_port']);
                $this->updateEnvVariable('REDIS_PORT', $validated['redis_port']);
            }

            if (isset($validated['redis_password'])) {
                Setting::set('redis_password', $validated['redis_password']);
                $this->updateEnvVariable('REDIS_PASSWORD', $validated['redis_password']);
            }

            if (isset($validated['redis_database'])) {
                Setting::set('redis_database', $validated['redis_database']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuración de websockets actualizada correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test queue connection
     */
    public function testQueue(Request $request)
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
            return response()->json([
                'success' => false,
                'message' => 'Error en la conexión: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restart queue workers
     */
    public function restartQueue()
    {
        try {
            Artisan::call('queue:restart');

            return response()->json([
                'success' => true,
                'message' => 'Workers de cola reiniciados correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reiniciar workers: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update environment variable in .env file
     */
    private function updateEnvVariable($key, $value)
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);
        $oldValue = env($key);

        if ($oldValue === null) {
            // Variable doesn't exist, add it
            $content .= "\n{$key}={$value}";
        } else {
            // Variable exists, replace it
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        }

        file_put_contents($path, $content);

        return true;
    }
}
