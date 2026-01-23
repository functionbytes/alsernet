<?php

namespace Modules\Health\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * HealthController
 *
 * Proporciona endpoints para verificar el estado de salud del sistema.
 * Útil para sistemas externos que necesitan verificar disponibilidad antes de enviar peticiones.
 */
class HealthController extends Controller
{
    /**
     * Health check simple - solo verifica que la aplicación responde
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => config('app.name'),
        ], 200);
    }

    /**
     * Health check completo - verifica múltiples servicios
     */
    public function health(): JsonResponse
    {
        $startTime = microtime(true);

        $checks = [
            'application' => $this->checkApplication(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        // Determinar estado general
        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');
        $overallStatus = $allHealthy ? 'healthy' : 'unhealthy';
        $httpCode = $allHealthy ? 200 : 503;

        // Calcular tiempo de respuesta
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => $overallStatus,
            'timestamp' => now()->toIso8601String(),
            'service' => config('app.name'),
            'version' => config('app.version', '1.0.0'),
            'response_time_ms' => $responseTime,
            'checks' => $checks,
        ], $httpCode);
    }

    /**
     * Health check específico para endpoints de documentos
     */
    public function documentsHealth(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorageAccess(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'unhealthy',
            'service' => 'documents-validation',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Verifica el estado de la aplicación
     */
    private function checkApplication(): array
    {
        try {
            return [
                'status' => 'healthy',
                'message' => 'Application is running',
                'debug_mode' => config('app.debug'),
                'environment' => config('app.env'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Application check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica la conexión a la base de datos
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);

            // Intentar una consulta simple
            DB::connection()->getPdo();
            $result = DB::select('SELECT 1 as test');

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'status' => 'healthy',
                'message' => 'Database connection is working',
                'connection' => config('database.default'),
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'connection' => config('database.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica la conexión al cache
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);
            $testKey = 'health_check_'.uniqid();
            $testValue = 'test_'.time();

            // Intentar escribir y leer del cache
            Cache::put($testKey, $testValue, 10);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache is working',
                    'driver' => config('cache.default'),
                    'response_time_ms' => $responseTime,
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Cache read/write test failed',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache check failed',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica acceso al sistema de archivos
     */
    private function checkStorageAccess(): array
    {
        try {
            $startTime = microtime(true);
            $testFile = storage_path('logs/health_check_test.txt');

            // Intentar escribir y leer un archivo
            file_put_contents($testFile, 'test');
            $content = file_get_contents($testFile);
            @unlink($testFile);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($content === 'test') {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage is writable',
                    'path' => storage_path(),
                    'response_time_ms' => $responseTime,
                ];
            }

            return [
                'status' => 'unhealthy',
                'message' => 'Storage read/write test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Health check detallado para debugging (solo en desarrollo)
     */
    public function detailed(): JsonResponse
    {
        // Solo disponible en desarrollo
        if (! config('app.debug')) {
            return response()->json([
                'error' => 'Detailed health check only available in debug mode',
            ], 403);
        }

        $startTime = microtime(true);

        $details = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->toDateTimeString(),
            'timezone' => config('app.timezone'),
            'memory_usage' => [
                'current' => round(memory_get_usage() / 1024 / 1024, 2).' MB',
                'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2).' MB',
            ],
            'disk_space' => [
                'free' => round(disk_free_space('/') / 1024 / 1024 / 1024, 2).' GB',
                'total' => round(disk_total_space('/') / 1024 / 1024 / 1024, 2).' GB',
            ],
            'checks' => [
                'application' => $this->checkApplication(),
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorageAccess(),
            ],
        ];

        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        return response()->json([
            'status' => 'ok',
            'response_time_ms' => $responseTime,
            'details' => $details,
        ], 200);
    }
}
