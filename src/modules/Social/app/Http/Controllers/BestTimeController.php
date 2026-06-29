<?php

namespace Modules\Social\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Social\Models\SocialAccount;
use Modules\Social\Services\BestTimeToPostService;

class BestTimeController extends Controller
{
    public function __construct(
        protected BestTimeToPostService $bestTimeService
    ) {}

    /**
     * Show best times dashboard
     */
    public function index(Request $request): View
    {
        $accountId = auth()->user()->account_id;

        // Get analysis for all networks
        $analysis = $this->bestTimeService->getBestTimesOverall($accountId, 90);

        // Get available social accounts
        $socialAccounts = SocialAccount::where('account_id', $accountId)
            ->where('status', 1)
            ->get()
            ->groupBy('network');

        return view('social::best-time.index', compact('analysis', 'socialAccounts'));
    }

    /**
     * Get best times for specific network (AJAX)
     */
    public function getForNetwork(Request $request, string $network): JsonResponse
    {
        $accountId = auth()->user()->account_id;
        $days = $request->get('days', 90);

        $analysis = $this->bestTimeService->getBestTimesForNetwork($accountId, $network, $days);

        return response()->json($analysis);
    }

    /**
     * Get best times for specific account (AJAX)
     */
    public function getForAccount(Request $request, SocialAccount $account): JsonResponse
    {
        // Verify ownership
        if ($account->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $days = $request->get('days', 90);

        $analysis = $this->bestTimeService->getBestTimesForAccount($account, $days);

        return response()->json($analysis);
    }

    /**
     * Suggest next posting time for account
     */
    public function suggestNextTime(SocialAccount $account): JsonResponse
    {
        // Verify ownership
        if ($account->account_id !== auth()->user()->account_id) {
            abort(403);
        }

        $nextTime = $this->bestTimeService->suggestNextPostingTime($account);

        if (! $nextTime) {
            return response()->json([
                'success' => false,
                'message' => 'No hay suficientes datos para hacer una recomendación',
            ]);
        }

        return response()->json([
            'success' => true,
            'suggested_time' => $nextTime->toIso8601String(),
            'formatted_time' => $nextTime->format('l, F j, Y \a\t g:i A'),
            'from_now' => $nextTime->diffForHumans(),
        ]);
    }
}
