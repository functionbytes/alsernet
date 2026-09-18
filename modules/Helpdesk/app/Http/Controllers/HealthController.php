<?php

namespace Modules\Helpdesk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        return $this->jsonSnapshot($this->snapshot(false));
    }

    /**
     * Authenticated diagnostics for Settings > Helpdesk > Salud.
     * Sensitive connection details never go through the public endpoint.
     */
    public function panel(): View
    {
        return view('helpdesk::settings.health.index', [
            'health' => $this->snapshot(true),
        ]);
    }

    /**
     * @return array{status: string, checks: array<string, array<string, mixed>>, timestamp: string}
     */
    private function snapshot(bool $detailed): array
    {
        $checks = [
            'mysql' => $this->checkMysql($detailed),
            'redis' => $this->checkRedis($detailed),
            'horizon' => $this->checkHorizon($detailed),
            'reverb' => $this->checkReverb($detailed),
            'tunnel' => $this->checkTunnel($detailed),
            'queue_pending' => $this->checkQueuePending($detailed),
        ];

        $statuses = collect($checks)->pluck('ok');
        $globalStatus = match (true) {
            ! $checks['mysql']['ok'] || ! $checks['redis']['ok'] => 'down',
            $statuses->contains(false) => 'degraded',
            default => 'ok',
        };

        return [
            'status' => $globalStatus,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /** @param array{status: string, checks: array<string, array<string, mixed>>, timestamp: string} $snapshot */
    private function jsonSnapshot(array $snapshot): JsonResponse
    {
        return response()->json($snapshot, $snapshot['status'] === 'down' ? 503 : 200);
    }

    private function checkMysql(bool $detailed): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();

            return ['ok' => true, 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        } catch (\Throwable $e) {
            return $this->failure($e, $detailed);
        }
    }

    private function checkRedis(bool $detailed): array
    {
        try {
            $start = microtime(true);
            Redis::ping();

            return ['ok' => true, 'latency_ms' => round((microtime(true) - $start) * 1000, 1)];
        } catch (\Throwable $e) {
            return $this->failure($e, $detailed);
        }
    }

    private function checkHorizon(bool $detailed): array
    {
        try {
            return [
                'ok' => true,
                'configured' => filled(config('horizon.environments')),
            ];
        } catch (\Throwable $e) {
            return $this->failure($e, $detailed);
        }
    }

    private function checkReverb(bool $detailed): array
    {
        $check = [
            'ok' => filled(config('reverb.servers.reverb.host')),
        ];

        if ($detailed) {
            $check['host'] = config('reverb.servers.reverb.host');
            $check['port'] = config('reverb.servers.reverb.port');
        }

        return $check;
    }

    private function checkTunnel(bool $detailed): array
    {
        $url = config('helpdesk.public_url');
        if (! $url) {
            return $detailed
                ? ['ok' => false, 'error' => 'HELPDESK_PUBLIC_URL no está configurada.']
                : ['ok' => false];
        }
        try {
            // Hit the webhook verify endpoint with an obviously-wrong token: a 403
            // response means the tunnel is alive AND Laravel is processing the
            // request correctly. Anything else (5xx / connect timeout) is a real issue.
            $probeUrl = rtrim($url, '/').'/api/helpdesk/webhooks/facebook?hub.mode=subscribe&hub.challenge=health&hub.verify_token=health';
            $response = Http::timeout(3)->withOptions(['verify' => false])->get($probeUrl);
            $alive = in_array($response->status(), [200, 403], true);

            $check = ['ok' => $alive, 'status' => $response->status()];
            if ($detailed) {
                $check['url'] = $url;
            }

            return $check;
        } catch (\Throwable $e) {
            return $this->failure($e, $detailed);
        }
    }

    private function checkQueuePending(bool $detailed): array
    {
        try {
            $prefix = config('database.redis.options.prefix', '');
            $count = Redis::connection()->llen($prefix.'queues:helpdesk-webhooks');

            return ['ok' => $count < 1000, 'count' => (int) $count];
        } catch (\Throwable $e) {
            return $this->failure($e, $detailed);
        }
    }

    private function failure(\Throwable $exception, bool $detailed): array
    {
        $failure = ['ok' => false];

        if ($detailed) {
            $failure['error'] = substr($exception->getMessage(), 0, 200);
        }

        return $failure;
    }
}
