<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
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

        $since30d = now()->subDays(30);

        $stats = [
            'stores_count' => Store::query()
                ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
                ->count(),

            'customers_count' => Customer::query()
                ->whereIn('store_id', $storeIds)
                ->count(),

            'subscribed_count' => Customer::query()
                ->whereIn('store_id', $storeIds)
                ->where('status', 'subscribed')
                ->count(),

            'campaigns_sent_30d' => Campaign::query()
                ->whereIn('store_id', $storeIds)
                ->where('status', 'sent')
                ->where('started_at', '>=', $since30d)
                ->count(),

            'revenue_30d' => Message::query()
                ->whereIn('store_id', $storeIds)
                ->where('sent_at', '>=', $since30d)
                ->sum('revenue'),

            'bounce_rate_30d' => $this->calcBounceRate($storeIds, $since30d),
            'open_rate_30d' => $this->calcOpenRate($storeIds, $since30d),
        ];

        $recent_messages = Message::query()
            ->with(['customer', 'campaign'])
            ->whereIn('store_id', $storeIds)
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->limit(20)
            ->get();

        $recent_carts = Cart::query()
            ->with(['customer', 'store'])
            ->whereIn('store_id', $storeIds)
            ->where('status', 'abandoned')
            ->latest('abandoned_at')
            ->limit(10)
            ->get();

        return view('remarketing::dashboard.index', compact('stats', 'recent_messages', 'recent_carts'));
    }

    private function getUserStoreIds(mixed $user): array
    {
        return Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->all();
    }

    private function calcBounceRate(array $storeIds, mixed $since): float
    {
        $total = Message::query()
            ->whereIn('store_id', $storeIds)
            ->where('sent_at', '>=', $since)
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $bounced = Message::query()
            ->whereIn('store_id', $storeIds)
            ->where('sent_at', '>=', $since)
            ->where('status', 'bounced')
            ->count();

        return round(($bounced / $total) * 100, 2);
    }

    private function calcOpenRate(array $storeIds, mixed $since): float
    {
        $delivered = Message::query()
            ->whereIn('store_id', $storeIds)
            ->where('sent_at', '>=', $since)
            ->whereIn('status', ['delivered', 'opened', 'clicked'])
            ->count();

        if ($delivered === 0) {
            return 0.0;
        }

        $opened = Message::query()
            ->whereIn('store_id', $storeIds)
            ->where('sent_at', '>=', $since)
            ->whereNotNull('opened_at')
            ->count();

        return round(($opened / $delivered) * 100, 2);
    }
}
