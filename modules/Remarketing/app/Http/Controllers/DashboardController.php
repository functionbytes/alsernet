<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Cart;
use Modules\Remarketing\Models\Customer;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Store;

class DashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();
        $storeIds = $this->getUserStoreIds($user);

        $currentStart = now()->subDays(30);
        $currentEnd = now();
        $previousStart = now()->subDays(60);
        $previousEnd = now()->subDays(30);

        $current = $this->computePeriodStats($storeIds, $user, $currentStart, $currentEnd);
        $previous = $this->computePeriodStats($storeIds, $user, $previousStart, $previousEnd);

        $stats = [
            'current' => $current,
            'previous' => $previous,
            'delta' => $this->computeDelta($current, $previous),
        ];

        $recentMessages = Message::query()
            ->with(['customer', 'campaign'])
            ->whereIn('store_id', $storeIds)
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->limit(20)
            ->get();

        $recentCarts = Cart::query()
            ->with(['customer', 'store'])
            ->whereIn('store_id', $storeIds)
            ->where('status', 'abandoned')
            ->latest('abandoned_at')
            ->limit(10)
            ->get();

        return view('remarketing::dashboard.index', compact('stats', 'recentMessages', 'recentCarts'));
    }

    public function chartData(): JsonResponse
    {
        $user = auth()->user();
        $storeIds = $this->getUserStoreIds($user);
        $months = request()->integer('months', 12);

        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            $labels[] = $start->format('M Y');

            $data[] = (float) Message::query()
                ->whereIn('store_id', $storeIds)
                ->whereBetween('sent_at', [$start, $end])
                ->sum('revenue');
        }

        return response()->json([
            'labels' => $labels,
            'datasets' => [
                ['label' => 'Revenue', 'data' => $data],
            ],
        ]);
    }

    private function computePeriodStats(array $storeIds, mixed $user, Carbon $start, Carbon $end): array
    {
        return [
            'stores_count' => Store::query()
                ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
                ->where('created_at', '<=', $end)
                ->count(),

            'customers_count' => Customer::query()
                ->whereIn('store_id', $storeIds)
                ->where('created_at', '<=', $end)
                ->count(),

            'subscribed_count' => Customer::query()
                ->whereIn('store_id', $storeIds)
                ->where('status', 'subscribed')
                ->where('created_at', '<=', $end)
                ->count(),

            'campaigns_sent' => Campaign::query()
                ->whereIn('store_id', $storeIds)
                ->where('status', 'sent')
                ->whereBetween('started_at', [$start, $end])
                ->count(),

            'revenue' => (float) Message::query()
                ->whereIn('store_id', $storeIds)
                ->whereBetween('sent_at', [$start, $end])
                ->sum('revenue'),

            'bounce_rate' => $this->calcBounceRate($storeIds, $start, $end),
            'open_rate' => $this->calcOpenRate($storeIds, $start, $end),
        ];
    }

    /**
     * Calcula el delta % entre current y previous para cada métrica.
     * Para bounce_rate (inverso) se invierte el signo: bajar es positivo.
     */
    private function computeDelta(array $current, array $previous): array
    {
        $delta = [];
        $inverseMetrics = ['bounce_rate'];

        foreach ($current as $key => $value) {
            $prev = $previous[$key] ?? 0;
            $diff = $value - $prev;
            $pct = $prev > 0 ? ($diff / $prev) * 100 : ($value > 0 ? 100.0 : 0.0);

            $isPositive = in_array($key, $inverseMetrics, true) ? $diff < 0 : $diff > 0;

            $delta[$key] = [
                'absolute' => round($diff, 2),
                'percent' => round($pct, 1),
                'is_positive' => $isPositive,
            ];
        }

        return $delta;
    }

    private function getUserStoreIds(mixed $user): array
    {
        return Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->all();
    }

    private function calcBounceRate(array $storeIds, Carbon $start, Carbon $end): float
    {
        $total = Message::query()
            ->whereIn('store_id', $storeIds)
            ->whereBetween('sent_at', [$start, $end])
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $bounced = Message::query()
            ->whereIn('store_id', $storeIds)
            ->whereBetween('sent_at', [$start, $end])
            ->where('status', 'bounced')
            ->count();

        return round(($bounced / $total) * 100, 2);
    }

    private function calcOpenRate(array $storeIds, Carbon $start, Carbon $end): float
    {
        $delivered = Message::query()
            ->whereIn('store_id', $storeIds)
            ->whereBetween('sent_at', [$start, $end])
            ->whereIn('status', ['delivered', 'opened', 'clicked'])
            ->count();

        if ($delivered === 0) {
            return 0.0;
        }

        $opened = Message::query()
            ->whereIn('store_id', $storeIds)
            ->whereBetween('sent_at', [$start, $end])
            ->whereNotNull('opened_at')
            ->count();

        return round(($opened / $delivered) * 100, 2);
    }
}
