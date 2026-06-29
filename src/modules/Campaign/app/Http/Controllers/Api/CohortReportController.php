<?php

namespace Modules\Campaign\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CohortReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $period = $request->validate(['period' => 'in:week,month'])['period'] ?? 'month';
        $months = min(24, max(1, (int) $request->input('months', 6)));

        $dateFormat = $period === 'week' ? '%x-w%v' : '%Y-%m';
        $since = now()->subMonths($months)->startOfMonth();

        // Cohorts base: subscribers grouped by subscription period
        $cohorts = DB::table('campaign_subscribers')
            ->select(
                DB::raw("DATE_FORMAT(subscribed_at, '{$dateFormat}') as cohort"),
                DB::raw('COUNT(*) as subscribers')
            )
            ->whereNotNull('subscribed_at')
            ->where('subscribed_at', '>=', $since)
            ->groupBy('cohort')
            ->orderBy('cohort')
            ->get()
            ->keyBy('cohort');

        if ($cohorts->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $cohortKeys = $cohorts->keys()->all();
        $placeholders = implode(',', array_fill(0, count($cohortKeys), '?'));

        // Open rate per cohort (first 30 days after subscription)
        $opens = DB::select("
            SELECT
                DATE_FORMAT(s.subscribed_at, '{$dateFormat}') as cohort,
                COUNT(DISTINCT s.id) as openers,
                COUNT(DISTINCT t.id) as senders
            FROM campaign_subscribers s
            LEFT JOIN campaign_tracking_logs t
                ON t.subscriber_id = s.id
                AND t.status = 'sent'
                AND t.created_at BETWEEN s.subscribed_at AND DATE_ADD(s.subscribed_at, INTERVAL 30 DAY)
            LEFT JOIN campaign_open_logs o
                ON o.tracking_log_id = t.id
                AND o.created_at BETWEEN s.subscribed_at AND DATE_ADD(s.subscribed_at, INTERVAL 30 DAY)
            WHERE DATE_FORMAT(s.subscribed_at, '{$dateFormat}') IN ({$placeholders})
            GROUP BY cohort
        ", $cohortKeys);

        // Click rate per cohort (first 30 days after subscription)
        $clicks = DB::select("
            SELECT
                DATE_FORMAT(s.subscribed_at, '{$dateFormat}') as cohort,
                COUNT(DISTINCT s.id) as clickers
            FROM campaign_subscribers s
            JOIN campaign_tracking_logs t
                ON t.subscriber_id = s.id
                AND t.status = 'sent'
                AND t.created_at BETWEEN s.subscribed_at AND DATE_ADD(s.subscribed_at, INTERVAL 30 DAY)
            JOIN campaign_click_logs c
                ON c.tracking_log_id = t.id
                AND c.created_at BETWEEN s.subscribed_at AND DATE_ADD(s.subscribed_at, INTERVAL 30 DAY)
            WHERE DATE_FORMAT(s.subscribed_at, '{$dateFormat}') IN ({$placeholders})
            GROUP BY cohort
        ", $cohortKeys);

        // Retention: still subscribed after 90 days
        $retention = DB::select("
            SELECT
                DATE_FORMAT(s.subscribed_at, '{$dateFormat}') as cohort,
                COUNT(*) as total,
                SUM(CASE WHEN s.unsubscribed_at IS NULL AND s.deleted_at IS NULL THEN 1 ELSE 0 END) as retained
            FROM campaign_subscribers s
            WHERE DATE_FORMAT(s.subscribed_at, '{$dateFormat}') IN ({$placeholders})
              AND s.subscribed_at <= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY cohort
        ", $cohortKeys);

        $openMap = collect($opens)->keyBy('cohort');
        $clickMap = collect($clicks)->keyBy('cohort');
        $retentionMap = collect($retention)->keyBy('cohort');

        $result = $cohorts->map(function ($row) use ($openMap, $clickMap, $retentionMap) {
            $cohort = $row->cohort;
            $senders = (int) ($openMap[$cohort]->senders ?? 0);
            $openers = (int) ($openMap[$cohort]->openers ?? 0);
            $clickers = (int) ($clickMap[$cohort]->clickers ?? 0);
            $retained = (int) ($retentionMap[$cohort]->retained ?? 0);
            $retTotal = (int) ($retentionMap[$cohort]->total ?? 0);

            return [
                'cohort' => $cohort,
                'subscribers' => (int) $row->subscribers,
                'open_rate_30d' => $senders > 0 ? round(($openers / $senders) * 100, 2) : 0,
                'click_rate_30d' => $senders > 0 ? round(($clickers / $senders) * 100, 2) : 0,
                'retention_90d' => $retTotal > 0 ? round(($retained / $retTotal) * 100, 2) : null,
            ];
        })->values();

        return response()->json(['data' => $result]);
    }
}
