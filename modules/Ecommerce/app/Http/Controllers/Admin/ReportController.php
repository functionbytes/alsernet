<?php

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Ecommerce\Enums\OrderStatus;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\SearchLog;
use Modules\Ecommerce\Services\DemandForecastService;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = $request->date('start_date', 'Y-m-d') ?? now()->startOfMonth()->toDateString();
        $endDate = $request->date('end_date', 'Y-m-d') ?? now()->toDateString();

        $baseQuery = Order::query()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);

        $totalRevenue = (clone $baseQuery)->where('payment_status', 'paid')->sum('total');
        $totalOrders = (clone $baseQuery)->count();
        $paidOrders = (clone $baseQuery)->where('payment_status', 'paid')->count();
        $pendingOrders = (clone $baseQuery)->where('status', OrderStatus::PENDING)->count();
        $processingOrders = (clone $baseQuery)->where('status', OrderStatus::PROCESSING)->count();
        $completedOrders = (clone $baseQuery)->where('status', OrderStatus::COMPLETED)->count();
        $cancelledOrders = (clone $baseQuery)->where('status', OrderStatus::CANCELLED)->count();

        $topProducts = OrderItem::query()
            ->selectRaw('product_id, product_name, product_image, SUM(qty) as total_qty, SUM(total) as total_revenue')
            ->whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->where('payment_status', 'paid');
            })
            ->groupBy('product_id', 'product_name', 'product_image')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $recentOrders = Order::query()
            ->with('customer')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->latest()
            ->limit(10)
            ->get();

        $lowStockProducts = Product::query()
            ->where('with_storehouse_management', true)
            ->where('quantity', '<=', 5)
            ->orderBy('quantity')
            ->limit(10)
            ->get();

        return view('ecommerce::reports.index', compact(
            'startDate', 'endDate',
            'totalRevenue', 'totalOrders', 'paidOrders',
            'pendingOrders', 'processingOrders', 'completedOrders', 'cancelledOrders',
            'topProducts', 'recentOrders', 'lowStockProducts'
        ));
    }

    public function comparison(Request $request): View
    {
        $period = $request->input('period', 'month');
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->resolvePeriods($period);

        $current = $this->statsForPeriod($currentStart, $currentEnd);
        $previous = $this->statsForPeriod($previousStart, $previousEnd);

        $delta = [
            'revenue' => $this->percentChange($previous['revenue'], $current['revenue']),
            'orders' => $this->percentChange($previous['orders'], $current['orders']),
            'aov' => $this->percentChange($previous['aov'], $current['aov']),
            'customers' => $this->percentChange($previous['customers'], $current['customers']),
        ];

        $currentSeries = $this->dailyRevenueSeries($currentStart, $currentEnd);
        $previousSeries = $this->dailyRevenueSeries($previousStart, $previousEnd);

        return view('ecommerce::reports.comparison', compact(
            'current', 'previous', 'delta', 'currentSeries', 'previousSeries', 'period'
        ));
    }

    public function funnel(Request $request): View
    {
        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $dateRange = [$from, $to.' 23:59:59'];

        $cartCount = DB::table('ecommerce_carts')
            ->whereBetween('created_at', $dateRange)
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        $checkoutStarted = DB::table('ecommerce_orders')
            ->whereBetween('created_at', $dateRange)
            ->count();

        $ordersPaid = DB::table('ecommerce_orders')
            ->whereBetween('created_at', $dateRange)
            ->where('payment_status', 'paid')
            ->count();

        $ordersCompleted = DB::table('ecommerce_orders')
            ->whereBetween('created_at', $dateRange)
            ->where('status', OrderStatus::COMPLETED->value)
            ->count();

        $funnel = [
            ['label' => 'Carritos creados',  'value' => $cartCount,       'rate' => 100],
            ['label' => 'Checkout iniciado', 'value' => $checkoutStarted, 'rate' => $cartCount > 0 ? round(($checkoutStarted / $cartCount) * 100, 1) : 0],
            ['label' => 'Pago confirmado',   'value' => $ordersPaid,      'rate' => $checkoutStarted > 0 ? round(($ordersPaid / $checkoutStarted) * 100, 1) : 0],
            ['label' => 'Orden completada',  'value' => $ordersCompleted, 'rate' => $ordersPaid > 0 ? round(($ordersCompleted / $ordersPaid) * 100, 1) : 0],
        ];

        return view('ecommerce::reports.funnel', compact('funnel', 'from', 'to'));
    }

    public function customerLtv(Request $request): View
    {
        $customers = DB::table('ecommerce_orders')
            ->join('ecommerce_customers', 'ecommerce_customers.id', '=', 'ecommerce_orders.customer_id')
            ->where('ecommerce_orders.payment_status', 'paid')
            ->select(
                'ecommerce_customers.id',
                'ecommerce_customers.name',
                'ecommerce_customers.email',
                DB::raw('COUNT(ecommerce_orders.id) as orders_count'),
                DB::raw('SUM(ecommerce_orders.total) as ltv'),
                DB::raw('AVG(ecommerce_orders.total) as aov'),
                DB::raw('MAX(ecommerce_orders.created_at) as last_order_at'),
                DB::raw('MIN(ecommerce_orders.created_at) as first_order_at'),
            )
            ->groupBy('ecommerce_customers.id', 'ecommerce_customers.name', 'ecommerce_customers.email')
            ->orderByDesc('ltv')
            ->paginate(25);

        $stats = [
            'avg_ltv' => $customers->getCollection()->avg('ltv') ?? 0,
            'avg_orders' => $customers->getCollection()->avg('orders_count') ?? 0,
            'total_revenue' => $customers->getCollection()->sum('ltv'),
        ];

        return view('ecommerce::reports.customer-ltv', compact('customers', 'stats'));
    }

    public function searchAnalytics(Request $request): View
    {
        $period = (int) $request->input('period', 30);

        $topSearches = SearchLog::query()
            ->where('created_at', '>=', now()->subDays($period))
            ->select('query', DB::raw('COUNT(*) as searches'), DB::raw('AVG(results_count) as avg_results'))
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(50)
            ->get();

        $zeroResults = SearchLog::query()
            ->where('created_at', '>=', now()->subDays($period))
            ->where('results_count', 0)
            ->select('query', DB::raw('COUNT(*) as searches'))
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(20)
            ->get();

        return view('ecommerce::reports.search-analytics', compact('topSearches', 'zeroResults', 'period'));
    }

    public function abandonedCarts(Request $request): View
    {
        $hours = (int) $request->input('hours', 24);
        $abandonedSince = now()->subHours($hours);

        $carts = DB::table('ecommerce_carts')
            ->join('ecommerce_customers', 'ecommerce_customers.id', '=', 'ecommerce_carts.customer_id')
            ->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_carts.product_id')
            ->where('ecommerce_carts.updated_at', '<=', $abandonedSince)
            ->select(
                'ecommerce_customers.id as customer_id',
                'ecommerce_customers.name',
                'ecommerce_customers.email',
                DB::raw('COUNT(ecommerce_carts.id) as items_count'),
                DB::raw('SUM(ecommerce_products.price * ecommerce_carts.qty) as cart_total'),
                DB::raw('MAX(ecommerce_carts.updated_at) as last_activity'),
            )
            ->groupBy('ecommerce_customers.id', 'ecommerce_customers.name', 'ecommerce_customers.email')
            ->orderByDesc('cart_total')
            ->paginate(25);

        $stats = [
            'count' => $carts->total(),
            'total_value' => $carts->getCollection()->sum('cart_total'),
        ];

        return view('ecommerce::reports.abandoned-carts', compact('carts', 'stats', 'hours'));
    }

    public function marginAnalysis(Request $request): View
    {
        $period = (int) $request->input('period', 30);
        $sortBy = $request->input('sort_by', 'margin_amount');

        $allowedSorts = ['margin_amount', 'units_sold', 'revenue'];
        $orderColumn = in_array($sortBy, $allowedSorts) ? $sortBy : 'margin_amount';

        $products = DB::table('ecommerce_order_items')
            ->join('ecommerce_orders', 'ecommerce_orders.id', '=', 'ecommerce_order_items.order_id')
            ->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_order_items.product_id')
            ->where('ecommerce_orders.payment_status', 'paid')
            ->where('ecommerce_orders.created_at', '>=', now()->subDays($period))
            ->select(
                'ecommerce_products.id',
                'ecommerce_products.name',
                'ecommerce_products.sku',
                'ecommerce_products.cost_per_item',
                'ecommerce_products.price',
                DB::raw('SUM(ecommerce_order_items.qty) as units_sold'),
                DB::raw('SUM(ecommerce_order_items.qty * ecommerce_order_items.price) as revenue'),
                DB::raw('SUM(ecommerce_order_items.qty * COALESCE(ecommerce_products.cost_per_item, 0)) as total_cost'),
                DB::raw('SUM(ecommerce_order_items.qty * (ecommerce_order_items.price - COALESCE(ecommerce_products.cost_per_item, 0))) as margin_amount')
            )
            ->groupBy(
                'ecommerce_products.id',
                'ecommerce_products.name',
                'ecommerce_products.sku',
                'ecommerce_products.cost_per_item',
                'ecommerce_products.price'
            )
            ->orderByDesc($orderColumn)
            ->limit(50)
            ->get()
            ->map(function ($p) {
                $marginPercent = $p->revenue > 0
                    ? round(($p->margin_amount / $p->revenue) * 100, 1)
                    : 0;

                return (object) array_merge((array) $p, ['margin_percent' => $marginPercent]);
            });

        $totalRevenue = $products->sum('revenue');
        $totalCost = $products->sum('total_cost');
        $totalMargin = $products->sum('margin_amount');

        $totals = [
            'revenue' => $totalRevenue,
            'cost' => $totalCost,
            'margin' => $totalMargin,
            'margin_percent' => $totalRevenue > 0 ? round(($totalMargin / $totalRevenue) * 100, 1) : 0,
        ];

        return view('ecommerce::reports.margin-analysis', compact('products', 'totals', 'period', 'sortBy'));
    }

    public function demandForecast(Request $request): View
    {
        $daysAhead = (int) $request->input('days_ahead', 30);
        $forecasts = app(DemandForecastService::class)->forecast($daysAhead);

        $productIds = collect($forecasts)->pluck('product_id');

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->select('id', 'name', 'sku', 'quantity', 'with_storehouse_management')
            ->get()
            ->keyBy('id');

        $rows = collect($forecasts)
            ->map(function (array $f) use ($products) {
                $product = $products->get($f['product_id']);

                if (! $product) {
                    return null;
                }

                $currentStock = (int) ($product->quantity ?? 0);
                $needsRestock = $product->with_storehouse_management
                    && $currentStock < $f['forecasted_units'];

                return array_merge($f, [
                    'product' => $product,
                    'current_stock' => $currentStock,
                    'needs_restock' => $needsRestock,
                    'restock_quantity' => $needsRestock ? max(0, $f['forecasted_units'] - $currentStock) : 0,
                ]);
            })
            ->filter()
            ->take(50);

        return view('ecommerce::reports.demand-forecast', compact('rows', 'daysAhead'));
    }

    public function customerJourney(Request $request): View
    {
        $period = (int) $request->input('period', 7);
        $since = now()->subDays($period);

        $topUrls = DB::table('ecommerce_page_views')
            ->where('viewed_at', '>=', $since)
            ->select('url', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT session_id) as unique_sessions'))
            ->groupBy('url')
            ->orderByDesc('views')
            ->limit(20)
            ->get();

        $sessionsBeforeOrder = DB::table('ecommerce_orders')
            ->where('ecommerce_orders.created_at', '>=', $since)
            ->where('ecommerce_orders.payment_status', 'paid')
            ->whereNotNull('ecommerce_orders.customer_id')
            ->join('ecommerce_page_views', 'ecommerce_page_views.customer_id', '=', 'ecommerce_orders.customer_id')
            ->whereColumn('ecommerce_page_views.viewed_at', '<=', 'ecommerce_orders.created_at')
            ->select(
                'ecommerce_orders.id as order_id',
                DB::raw('COUNT(DISTINCT ecommerce_page_views.session_id) as sessions')
            )
            ->groupBy('ecommerce_orders.id')
            ->get();

        $avgSessions = $sessionsBeforeOrder->count() > 0
            ? round($sessionsBeforeOrder->avg('sessions'), 1)
            : 0;

        $topProducts = DB::table('ecommerce_page_views')
            ->whereNotNull('product_id')
            ->where('viewed_at', '>=', $since)
            ->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_page_views.product_id')
            ->select('ecommerce_products.name', 'ecommerce_products.id', DB::raw('COUNT(*) as views'))
            ->groupBy('ecommerce_products.id', 'ecommerce_products.name')
            ->orderByDesc('views')
            ->limit(20)
            ->get();

        return view('ecommerce::reports.customer-journey', compact('topUrls', 'avgSessions', 'topProducts', 'period'));
    }

    private function resolvePeriods(string $period): array
    {
        $now = now();

        return match ($period) {
            'week' => [$now->copy()->subWeek(),    $now, $now->copy()->subWeeks(2),  $now->copy()->subWeek()],
            'quarter' => [$now->copy()->subMonths(3), $now, $now->copy()->subMonths(6), $now->copy()->subMonths(3)],
            'year' => [$now->copy()->subYear(),    $now, $now->copy()->subYears(2),  $now->copy()->subYear()],
            default => [$now->copy()->subMonth(),   $now, $now->copy()->subMonths(2), $now->copy()->subMonth()],
        };
    }

    private function statsForPeriod($start, $end): array
    {
        $query = DB::table('ecommerce_orders')
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid');

        $total = (clone $query)->sum('total');
        $count = (clone $query)->count();
        $customers = (clone $query)->distinct('customer_id')->count('customer_id');

        return [
            'revenue' => (float) $total,
            'orders' => $count,
            'aov' => $count > 0 ? round($total / $count, 2) : 0,
            'customers' => $customers,
        ];
    }

    private function dailyRevenueSeries($start, $end): array
    {
        return DB::table('ecommerce_orders')
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', 'paid')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'revenue' => (float) $row->revenue])
            ->toArray();
    }

    private function percentChange(float|int $previous, float|int $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
