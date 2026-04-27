<?php

namespace Modules\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DemandForecastService
{
    /**
     * Forecast demand using average daily sales + linear trend comparison.
     *
     * @return array<int, array{product_id: int, historical_units: int, avg_daily: float, trend_factor: float, forecasted_units: int}>
     */
    public function forecast(int $daysAhead = 30, int $historicalDays = 60): array
    {
        $now = now();
        $historicalStart = $now->copy()->subDays($historicalDays);
        $halfwayPoint = $historicalStart->copy()->addDays((int) ($historicalDays / 2));

        $dailySales = DB::table('ecommerce_order_items')
            ->join('ecommerce_orders', 'ecommerce_orders.id', '=', 'ecommerce_order_items.order_id')
            ->where('ecommerce_orders.payment_status', 'paid')
            ->where('ecommerce_orders.created_at', '>=', $historicalStart)
            ->select(
                'ecommerce_order_items.product_id',
                DB::raw('DATE(ecommerce_orders.created_at) as date'),
                DB::raw('SUM(ecommerce_order_items.qty) as units')
            )
            ->groupBy('ecommerce_order_items.product_id', 'date')
            ->get();

        $forecasts = [];

        foreach ($dailySales->groupBy('product_id') as $productId => $sales) {
            $totalUnits = $sales->sum('units');
            $avgDaily = $totalUnits / $historicalDays;

            $firstHalf = $sales->filter(fn ($s) => Carbon::parse($s->date)->lt($halfwayPoint))->sum('units');
            $secondHalf = $sales->filter(fn ($s) => Carbon::parse($s->date)->gte($halfwayPoint))->sum('units');

            $trendFactor = $firstHalf > 0 ? ($secondHalf / $firstHalf) : 1.0;

            $forecasted = max(0, (int) round($avgDaily * $daysAhead * (($trendFactor + 1) / 2)));

            $forecasts[] = [
                'product_id' => $productId,
                'historical_units' => (int) $totalUnits,
                'avg_daily' => round($avgDaily, 2),
                'trend_factor' => round($trendFactor, 2),
                'forecasted_units' => $forecasted,
            ];
        }

        usort($forecasts, fn ($a, $b) => $b['forecasted_units'] - $a['forecasted_units']);

        return $forecasts;
    }
}
