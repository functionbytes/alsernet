<?php

namespace Modules\Remarketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Remarketing\Models\Automation;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Models\Message;
use Modules\Remarketing\Models\Store;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny-report');

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $campaigns = Campaign::query()
            ->whereIn('store_id', $storeIds)
            ->whereIn('status', ['sent', 'sending'])
            ->when($request->input('store_id'), fn ($q, $id) => $q->where('store_id', $id))
            ->select([
                'id', 'name', 'status', 'sent', 'delivered', 'opened',
                'clicked', 'bounced', 'unsubscribed', 'complained', 'revenue',
                'recipients_total', 'started_at', 'completed_at',
            ])
            ->latest('started_at')
            ->paginate(20);

        $automations = Automation::query()
            ->with(['store'])
            ->whereIn('store_id', $storeIds)
            ->withCount('runs')
            ->latest()
            ->get();

        $stores = Store::query()->whereIn('id', $storeIds)->get(['id', 'name']);

        $since30d = now()->subDays(30);
        $sentTotal = Message::query()
            ->whereIn('store_id', $storeIds)
            ->where('sent_at', '>=', $since30d)
            ->count();

        $stats = [
            'delivery_rate' => $this->calcRate($storeIds, $since30d, 'delivered', $sentTotal),
            'open_rate' => $this->calcRate($storeIds, $since30d, 'opened', $sentTotal),
            'ctr' => $this->calcRate($storeIds, $since30d, 'clicked', $sentTotal),
            'bounce_rate' => $this->calcRate($storeIds, $since30d, 'bounced', $sentTotal),
            'complaint_rate' => $this->calcRate($storeIds, $since30d, 'complained', $sentTotal),
            'unsubscribe_rate' => $this->calcRate($storeIds, $since30d, 'unsubscribed', $sentTotal),
            'revenue_per_recipient' => $sentTotal > 0
                ? round(Message::query()->whereIn('store_id', $storeIds)->where('sent_at', '>=', $since30d)->sum('revenue') / $sentTotal, 2)
                : 0,
        ];

        return view('remarketing::reports.index', compact('campaigns', 'automations', 'stores', 'stats'));
    }

    public function exportPdf(): Response
    {
        $this->authorize('viewAny-report');

        $user = auth()->user();

        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $since30d = now()->subDays(30);
        $sentTotal = Message::query()
            ->whereIn('store_id', $storeIds)
            ->where('sent_at', '>=', $since30d)
            ->count();

        $stats = [
            'delivery_rate' => $this->calcRate($storeIds, $since30d, 'delivered', $sentTotal),
            'open_rate' => $this->calcRate($storeIds, $since30d, 'opened', $sentTotal),
            'ctr' => $this->calcRate($storeIds, $since30d, 'clicked', $sentTotal),
            'bounce_rate' => $this->calcRate($storeIds, $since30d, 'bounced', $sentTotal),
            'complaint_rate' => $this->calcRate($storeIds, $since30d, 'complained', $sentTotal),
            'unsubscribe_rate' => $this->calcRate($storeIds, $since30d, 'unsubscribed', $sentTotal),
            'revenue_per_recipient' => $sentTotal > 0
                ? round(Message::query()->whereIn('store_id', $storeIds)->where('sent_at', '>=', $since30d)->sum('revenue') / $sentTotal, 2)
                : 0,
        ];

        $campaigns = Campaign::query()
            ->whereIn('store_id', $storeIds)
            ->whereIn('status', ['sent', 'sending'])
            ->select(['id', 'name', 'sent', 'opened', 'clicked', 'bounced', 'revenue', 'completed_at'])
            ->latest('started_at')
            ->limit(50)
            ->get()
            ->map(function (Campaign $c): array {
                $sent = max($c->sent, 1);

                return [
                    'name' => $c->name,
                    'sent' => $c->sent,
                    'open_rate' => $c->opened ? round(($c->opened / $sent) * 100, 1) : 0,
                    'ctr' => $c->clicked ? round(($c->clicked / $sent) * 100, 1) : 0,
                    'bounce_rate' => $c->bounced ? round(($c->bounced / $sent) * 100, 1) : 0,
                    'revenue' => (float) $c->revenue,
                    'completed_at' => $c->completed_at?->format('d/m/Y'),
                ];
            });

        $pdf = Pdf::loadView('remarketing::reports.pdf', [
            'stats' => $stats,
            'campaigns' => $campaigns,
            'since30d' => $since30d,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte-remarketing-'.now()->format('Y-m-d').'.pdf');
    }

    private function calcRate(array|Collection $storeIds, mixed $since, string $status, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        $count = Message::query()
            ->whereIn('store_id', $storeIds)
            ->where('sent_at', '>=', $since)
            ->where('status', $status)
            ->count();

        return round(($count / $total) * 100, 2);
    }

    public function chartData(): JsonResponse
    {
        $user = auth()->user();
        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->all();

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

        return response()->json(['labels' => $labels, 'data' => $data]);
    }

    public function campaignsData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $storeIds = Store::query()
            ->when(! $user->can('remarketing.manage'), fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id')
            ->all();

        $campaigns = Campaign::query()
            ->whereIn('store_id', $storeIds)
            ->whereIn('status', ['sent', 'sending'])
            ->select([
                'id', 'name', 'status', 'sent', 'delivered', 'opened',
                'clicked', 'bounced', 'unsubscribed', 'complained', 'revenue',
                'recipients_total', 'started_at', 'completed_at',
            ])
            ->latest('started_at')
            ->paginate(15);

        $items = $campaigns->map(function ($c) {
            $sent = max($c->sent, 1);

            return [
                'id' => $c->id,
                'name' => $c->name,
                'sent' => $c->sent,
                'open_rate' => $c->opened ? round(($c->opened / $sent) * 100, 1) : 0,
                'ctr' => $c->clicked ? round(($c->clicked / $sent) * 100, 1) : 0,
                'bounce_rate' => $c->bounced ? round(($c->bounced / $sent) * 100, 1) : 0,
                'revenue' => (float) $c->revenue,
                'completed_at' => $c->completed_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'data' => $items,
            'totalCount' => $campaigns->total(),
        ]);
    }
}
