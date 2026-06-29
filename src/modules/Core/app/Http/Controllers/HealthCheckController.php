<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheckController extends Controller
{
    /**
     * Fast health check endpoint for load balancers and monitoring tools.
     *
     * GET /health
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => true,
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];

        if (extension_loaded('redis') && config('database.redis.default')) {
            $checks['redis'] = $this->checkRedis();
        }

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health_check', time(), 10);

            return Cache::get('health_check') !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
            Storage::disk('local')->delete('health_check.txt');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            Redis::ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
