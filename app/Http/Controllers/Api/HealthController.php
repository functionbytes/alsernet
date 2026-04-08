<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    use ApiResponse;

    public function ping(): JsonResponse
    {
        return $this->apiSuccess(['status' => 'ok', 'timestamp' => now()->toIso8601String()]);
    }

    public function status(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $allHealthy = ! in_array(false, $checks, true);

        return $this->apiSuccess(
            ['checks' => $checks],
            $allHealthy ? 'All systems operational' : 'Some systems degraded',
            $allHealthy ? 200 : 503
        );
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health_check', true, 5);

            return Cache::get('health_check') === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
