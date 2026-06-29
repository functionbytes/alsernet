<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\Campaign;

class GeoReportController extends Controller
{
    public function opensByCountry(string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $rows = DB::table('campaign_open_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_open_logs.tracking_log_id')
            ->where('campaign_tracking_logs.campaign_id', $campaign->id)
            ->whereNotNull('campaign_open_logs.country')
            ->select('campaign_open_logs.country', DB::raw('COUNT(DISTINCT campaign_open_logs.tracking_log_id) as unique_opens'))
            ->groupBy('campaign_open_logs.country')
            ->orderByDesc('unique_opens')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function clicksByCountry(string $uid): JsonResponse
    {
        $campaign = Campaign::where('uid', $uid)->firstOrFail();

        $rows = DB::table('campaign_click_logs')
            ->join('campaign_tracking_logs', 'campaign_tracking_logs.id', '=', 'campaign_click_logs.tracking_log_id')
            ->where('campaign_tracking_logs.campaign_id', $campaign->id)
            ->whereNotNull('campaign_click_logs.country')
            ->select('campaign_click_logs.country', DB::raw('COUNT(*) as clicks'))
            ->groupBy('campaign_click_logs.country')
            ->orderByDesc('clicks')
            ->get();

        return response()->json(['data' => $rows]);
    }
}
