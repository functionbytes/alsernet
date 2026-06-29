<?php

namespace Modules\Engagement\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Engagement\Models\AbTest;

class AbTestRevenueController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:engagement.ab_tests.view');
    }

    public function show(AbTest $abTest): JsonResponse
    {
        $rows = DB::connection('helpdesk')
            ->table('engagement_ab_test_attributions')
            ->select(
                'ab_test_variant_id',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(revenue) as total_revenue'),
                DB::raw('AVG(revenue) as avg_order_value'),
            )
            ->where('ab_test_id', $abTest->id)
            ->groupBy('ab_test_variant_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows->map(fn ($r) => [
                'variant_id' => (int) $r->ab_test_variant_id,
                'orders_count' => (int) $r->orders_count,
                'total_revenue' => (float) $r->total_revenue,
                'avg_order_value' => round((float) $r->avg_order_value, 2),
            ]),
        ]);
    }
}
