<?php

namespace Modules\Horizon\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Horizon\Services\HorizonStatsService;

class HorizonApiController extends Controller
{
    public function __construct(private HorizonStatsService $statsService)
    {
    }

    /**
     * Get overall queue statistics
     */
    public function stats(): JsonResponse
    {
        $this->authorize('view-horizon');

        try {
            $stats = $this->statsService->getOverallStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent jobs
     */
    public function recentJobs(Request $request): JsonResponse
    {
        $this->authorize('view-horizon');

        try {
            $limit = $request->input('limit', 50);
            $jobs = $this->statsService->getRecentJobs($limit);

            return response()->json([
                'success' => true,
                'data' => $jobs,
                'count' => count($jobs),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching recent jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get failed jobs
     */
    public function failedJobs(Request $request): JsonResponse
    {
        $this->authorize('view-horizon');

        try {
            $limit = $request->input('limit', 50);
            $jobs = $this->statsService->getFailedJobs($limit);

            return response()->json([
                'success' => true,
                'data' => $jobs,
                'count' => count($jobs),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching failed jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry a specific job
     */
    public function retryJob(Request $request, string $jobId): JsonResponse
    {
        $this->authorize('manage-horizon-backups');

        try {
            $result = $this->statsService->retryJob($jobId);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Job retry queued successfully' : 'Failed to retry job',
            ], $result ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrying job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry all failed jobs
     */
    public function retryAll(): JsonResponse
    {
        $this->authorize('manage-horizon-backups');

        try {
            $count = $this->statsService->retryAllFailed();

            return response()->json([
                'success' => true,
                'message' => "Queued {$count} failed jobs for retry",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrying all failed jobs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete/forget a failed job
     */
    public function forgetJob(Request $request, string $jobId): JsonResponse
    {
        $this->authorize('manage-horizon-backups');

        try {
            $result = $this->statsService->forgetJob($jobId);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Job deleted successfully' : 'Failed to delete job',
            ], $result ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
