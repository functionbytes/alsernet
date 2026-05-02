<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Services\HealthCheckService;

class HealthCheckController extends Controller
{
    public function __construct(
        protected HealthCheckService $healthCheckService
    ) {}

    /**
     * Show health check dashboard
     */
    public function index(): View
    {
        return view('social::health.index');
    }

    /**
     * Run all health checks and return JSON
     */
    public function check(): JsonResponse
    {
        $results = $this->healthCheckService->runAllChecks();
        $results['overall_status'] = $this->healthCheckService->getOverallHealth();

        return response()->json($results);
    }

    /**
     * Check specific component
     */
    public function checkComponent(string $component): JsonResponse
    {
        $method = 'check'.ucfirst($component);

        if (! method_exists($this->healthCheckService, $method)) {
            return response()->json([
                'error' => 'Invalid component',
            ], 404);
        }

        $result = $this->healthCheckService->$method();

        return response()->json([
            'component' => $component,
            'result' => $result,
        ]);
    }

    /**
     * Refresh specific OAuth token
     */
    public function refreshToken(int $accountId): JsonResponse
    {
        try {
            $account = SocialAccount::findOrFail($accountId);

            // Redirect to OAuth flow
            return response()->json([
                'redirect' => route('admin.social.oauth.redirect', $account->network->value),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs(): JsonResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => 'all']);

            return response()->json([
                'success' => true,
                'message' => 'All failed jobs have been queued for retry',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs(): JsonResponse
    {
        try {
            Artisan::call('queue:flush');

            return response()->json([
                'success' => true,
                'message' => 'All failed jobs have been cleared',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
