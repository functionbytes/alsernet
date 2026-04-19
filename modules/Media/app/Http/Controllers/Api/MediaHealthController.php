<?php

namespace Modules\Media\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

class MediaHealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'disks' => $this->checkDisks(),
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
            'permissions' => $this->checkPermissions(),
            'migrations' => $this->checkMigrations(),
            'storage_quota' => $this->checkStorageQuota(),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['status'] === 'ok');

        return response()->json([
            'healthy' => $healthy,
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function checkDisks(): array
    {
        $disks = ['media', 'public'];
        $results = [];

        foreach ($disks as $disk) {
            try {
                $testKey = '.health-check-'.time();
                Storage::disk($disk)->put($testKey, 'ok');
                $readBack = Storage::disk($disk)->get($testKey);
                Storage::disk($disk)->delete($testKey);
                $results[$disk] = $readBack === 'ok' ? 'writable' : 'read_failed';
            } catch (\Throwable $e) {
                $results[$disk] = 'failed: '.$e->getMessage();
            }
        }

        $allOk = collect($results)->every(fn ($r) => $r === 'writable');

        return [
            'status' => $allOk ? 'ok' : 'error',
            'details' => $results,
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $count = DB::table('media_files')->count();

            return ['status' => 'ok', 'details' => ['files_count' => $count]];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();

            return ['status' => 'ok', 'details' => 'ping ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $pending = DB::table('jobs')->count();

            return [
                'status' => 'ok',
                'details' => [
                    'connection' => $connection,
                    'pending_jobs' => $pending,
                ],
            ];
        } catch (\Throwable $e) {
            return ['status' => 'warning', 'details' => $e->getMessage()];
        }
    }

    private function checkPermissions(): array
    {
        $expected = [
            'media.view', 'media.create', 'media.update',
            'media.delete', 'media.manage', 'media.force-delete',
        ];

        $existing = Permission::whereIn('name', $expected)->pluck('name')->toArray();
        $missing = array_diff($expected, $existing);

        return [
            'status' => empty($missing) ? 'ok' : 'error',
            'details' => [
                'expected' => count($expected),
                'found' => count($existing),
                'missing' => array_values($missing),
            ],
        ];
    }

    private function checkMigrations(): array
    {
        try {
            $ran = DB::table('migrations')->where('migration', 'like', '%media%')->count();

            return [
                'status' => $ran >= 5 ? 'ok' : 'warning',
                'details' => ['ran' => $ran, 'expected_at_least' => 5],
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    private function checkStorageQuota(): array
    {
        if (! config('media.quota.enabled', false)) {
            return ['status' => 'ok', 'details' => 'quota disabled'];
        }

        try {
            $total = (int) DB::table('media_files')->sum('size');
            $quota = (int) config('media.quota.default_bytes', 1073741824);

            return [
                'status' => 'ok',
                'details' => [
                    'total_bytes' => $total,
                    'default_quota_per_user' => $quota,
                ],
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }
}
