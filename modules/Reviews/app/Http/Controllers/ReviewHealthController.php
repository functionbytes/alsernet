<?php

namespace Modules\Reviews\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Reviews\Enums\ConnectionStatus;
use Modules\Reviews\Enums\ReplyStatus;
use Modules\Reviews\Models\ReviewGoogleConnection;
use Modules\Reviews\Models\ReviewGoogleLocation;
use Modules\Reviews\Models\ReviewReply;

class ReviewHealthController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:reviews.reviews.view');
    }

    public function check(): JsonResponse
    {
        $checks = [
            'google_connections' => $this->checkGoogleConnections(),
            'pending_syncs' => $this->checkPendingSyncs(),
            'failed_replies' => $this->checkFailedReplies(),
            'last_sync' => $this->checkLastSync(),
        ];

        $status = $this->resolveOverallStatus($checks);

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'checked_at' => now()->toIso8601ZuluString(),
        ]);
    }

    /** @return array{status: string, active: int, expired: int} */
    private function checkGoogleConnections(): array
    {
        $active = ReviewGoogleConnection::query()->where('status', ConnectionStatus::ACTIVE)->count();
        $expired = ReviewGoogleConnection::query()->where('status', ConnectionStatus::EXPIRED)->count();

        $status = match (true) {
            $active === 0 => 'critical',
            $expired > 0 => 'warning',
            default => 'ok',
        };

        return compact('status', 'active', 'expired');
    }

    /** @return array{status: string, count: int} */
    private function checkPendingSyncs(): array
    {
        $count = $this->countPendingSyncJobs();

        $status = match (true) {
            $count > 50 => 'critical',
            $count > 20 => 'warning',
            default => 'ok',
        };

        return compact('status', 'count');
    }

    /** @return array{status: string, count: int} */
    private function checkFailedReplies(): array
    {
        $count = ReviewReply::query()->where('status', ReplyStatus::FAILED)->count();

        $status = match (true) {
            $count > 20 => 'critical',
            $count > 5 => 'warning',
            default => 'ok',
        };

        return compact('status', 'count');
    }

    /** @return array{status: string, at: string|null, minutes_ago: int|null} */
    private function checkLastSync(): array
    {
        $latest = ReviewGoogleLocation::query()
            ->whereNotNull('synced_at')
            ->max('synced_at');

        if (! $latest) {
            return ['status' => 'warning', 'at' => null, 'minutes_ago' => null];
        }

        $minutesAgo = (int) now()->diffInMinutes($latest);

        $status = match (true) {
            $minutesAgo > 60 => 'critical',
            $minutesAgo > 30 => 'warning',
            default => 'ok',
        };

        return [
            'status' => $status,
            'at' => Carbon::parse($latest)->toIso8601ZuluString(),
            'minutes_ago' => $minutesAgo,
        ];
    }

    private function countPendingSyncJobs(): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', 'default')
                ->where('payload', 'like', '%SyncGoogleReviewsJob%')
                ->count();
        } catch (\Exception) {
            return 0;
        }
    }

    /**
     * @param  array<string, array{status: string, ...}>  $checks
     */
    private function resolveOverallStatus(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('critical', $statuses, strict: true)) {
            return 'unhealthy';
        }

        if (in_array('warning', $statuses, strict: true)) {
            return 'degraded';
        }

        return 'healthy';
    }
}
