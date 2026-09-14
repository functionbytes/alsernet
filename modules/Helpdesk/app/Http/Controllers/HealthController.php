<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'mysql' => $this->checkMysql(),
            'redis' => $this->checkRedis(),
            'horizon' => $this->checkHorizon(),
            'reverb' => $this->checkReverb(),
            'tunnel' => $this->checkTunnel(),
            'queue_pending' => $this->checkQueuePending(),
        ];

        $statuses = collect($checks)->pluck('ok');
        $globalStatus = match (true) {
            ! $checks['mysql']['ok'] || ! $checks['redis']['ok'] => 'down',
            $statuses->contains(false) => 'degraded',
            default => 'ok',
        };

        return response()->json([
            'status' => $globalStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $globalStatus === 'down' ? 503 : 200);
    }

    private function checkMysql(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();

            return ['ok' => true, 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            $start = microtime(true);
            Redis::ping();

            return ['ok' => true, 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkHorizon(): array
    {
        try {
            return [
                'ok' => true,
                'configured' => filled(config('horizon.environments')),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkReverb(): array
    {
        return [
            'ok' => filled(config('reverb.servers.reverb.host')),
            'host' => config('reverb.servers.reverb.host'),
            'port' => config('reverb.servers.reverb.port'),
        ];
    }

    private function checkTunnel(): array
    {
        $url = config('helpdesk.public_url');
        if (! $url) {
            return ['ok' => false, 'error' => 'HELPDESK_PUBLIC_URL not configured'];
        }
        try {
            // Hit the webhook verify endpoint with an obviously-wrong token: a 403
            // response means the tunnel is alive AND Laravel is processing the
            // request correctly. Anything else (5xx / connect timeout) is a real issue.
            $probeUrl = rtrim($url, '/').'/api/helpdesk/webhooks/facebook?hub.mode=subscribe&hub.challenge=health&hub.verify_token=health';
            $response = Http::timeout(3)->withOptions(['verify' => false])->get($probeUrl);
            $alive = in_array($response->status(), [200, 403], true);

            return ['ok' => $alive, 'status' => $response->status(), 'url' => $url];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => substr($e->getMessage(), 0, 100)];
        }
    }

    private function checkQueuePending(): array
    {
        try {
            $prefix = config('database.redis.options.prefix', '');
            $count = Redis::connection()->llen($prefix.'queues:helpdesk-webhooks');

            return ['ok' => $count < 1000, 'count' => (int) $count];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
