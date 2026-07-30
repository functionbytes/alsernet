<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Helpdesk\Http\Requests\Managers\CsatReportDataRequest;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Models\Inbox;

class CsatReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.reports.view');
    }

    /**
     * GET /panel/helpdesk/reports/csat
     */
    public function index(): View
    {
        return view('helpdesk::helpdesk.reports.csat');
    }

    /**
     * GET /panel/helpdesk/reports/csat/data
     * Returns AJAX data for charts and tables.
     */
    public function data(CsatReportDataRequest $request): JsonResponse
    {
        $from = $request->filled('date_from')
            ? now()->parse($request->input('date_from'))->startOfDay()
            : now()->subDays(30)->startOfDay();

        $to = $request->filled('date_to')
            ? now()->parse($request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        // Mismo patrón de caché que los reports hermanos (Trends/Heatmap/...):
        // ~10 queries agregadas por request que no cambian en ventanas cortas.
        $cacheKey = sprintf('helpdesk:reports:csat:%s:%s', $from->toDateString(), $to->toDateString());

        $payload = Cache::remember($cacheKey, 300, function () use ($from, $to): array {
            $base = CsatRating::query()
                ->whereNotNull('answered_at')
                ->whereBetween('answered_at', [$from, $to]);

            return [
                'summary' => $this->getSummary(clone $base),
                'by_agent' => $this->getByAgent(clone $base),
                'by_inbox' => $this->getByInbox(clone $base),
                'by_channel' => $this->getByChannel(clone $base),
                'distribution' => $this->getDistribution(clone $base),
                'trend' => $this->getTrend(clone $base, $from, $to),
                'low_ratings' => $this->getLowRatings(clone $base),
            ];
        });

        return response()->json($payload);
    }

    private function getSummary($query): array
    {
        $row = (clone $query)->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')->first();
        $total = (int) ($row->total ?? 0);

        return [
            'total' => $total,
            'avg' => $total > 0 ? round((float) $row->avg_rating, 2) : null,
        ];
    }

    private function getByAgent($query): array
    {
        $rows = (clone $query)
            ->selectRaw('agent_id, AVG(rating) as avg_rating, COUNT(*) as total, MIN(rating) as min_rating')
            ->groupBy('agent_id')
            ->orderByRaw('AVG(rating) DESC')
            ->get();

        $userIds = $rows->pluck('agent_id')->filter()->all();
        $users = User::whereIn('id', $userIds)->get(['id', 'firstname', 'lastname'])->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $u = $users->get($row->agent_id);
            $name = $u
                ? (trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: 'Sin nombre')
                : 'Sin asignar';

            return [
                'agent_id' => $row->agent_id,
                'agent_name' => $name,
                'avg' => round((float) $row->avg_rating, 2),
                'count' => (int) $row->total,
                'lowest' => (int) $row->min_rating,
            ];
        })->values()->all();
    }

    private function getByInbox($query): array
    {
        $rows = (clone $query)
            ->join('helpdesk_conversations', 'helpdesk_csat_ratings.conversation_id', '=', 'helpdesk_conversations.id')
            ->selectRaw('helpdesk_conversations.inbox_id, AVG(helpdesk_csat_ratings.rating) as avg_rating, COUNT(*) as total')
            ->groupBy('helpdesk_conversations.inbox_id')
            ->orderByRaw('AVG(helpdesk_csat_ratings.rating) DESC')
            ->get();

        $inboxIds = $rows->pluck('inbox_id')->filter()->all();
        $inboxes = Inbox::whereIn('id', $inboxIds)->get(['id', 'name'])->keyBy('id');

        return $rows->map(fn ($row) => [
            'inbox_id' => $row->inbox_id,
            'inbox_name' => $inboxes->get($row->inbox_id)?->name ?? 'Sin bandeja',
            'avg' => round((float) $row->avg_rating, 2),
            'count' => (int) $row->total,
        ])->values()->all();
    }

    private function getByChannel($query): array
    {
        $rows = (clone $query)
            ->join('helpdesk_conversations', 'helpdesk_csat_ratings.conversation_id', '=', 'helpdesk_conversations.id')
            ->selectRaw('helpdesk_conversations.channel, AVG(helpdesk_csat_ratings.rating) as avg_rating, COUNT(*) as total')
            ->groupBy('helpdesk_conversations.channel')
            ->orderByRaw('AVG(helpdesk_csat_ratings.rating) DESC')
            ->get();

        return $rows->map(fn ($row) => [
            'channel' => $row->channel ?? 'unknown',
            'avg' => round((float) $row->avg_rating, 2),
            'count' => (int) $row->total,
        ])->values()->all();
    }

    private function getDistribution($query): array
    {
        $rows = (clone $query)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('total', 'rating');

        $distribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = (int) ($rows[$i] ?? 0);
        }

        return $distribution;
    }

    private function getTrend($query, $from, $to): array
    {
        $rows = (clone $query)
            ->selectRaw('DATE(answered_at) as day, AVG(rating) as avg_rating, COUNT(*) as total')
            ->groupByRaw('DATE(answered_at)')
            ->orderByRaw('DATE(answered_at)')
            ->get();

        return $rows->map(fn ($row) => [
            'day' => $row->day,
            'avg' => round((float) $row->avg_rating, 2),
            'count' => (int) $row->total,
        ])->values()->all();
    }

    private function getLowRatings($query): array
    {
        $rows = (clone $query)
            ->where('rating', '<=', 2)
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->orderBy('answered_at', 'desc')
            ->limit(20)
            ->get(['id', 'rating', 'comment', 'agent_id', 'answered_at']);

        $userIds = $rows->pluck('agent_id')->filter()->all();
        $users = User::whereIn('id', $userIds)->get(['id', 'firstname', 'lastname'])->keyBy('id');

        return $rows->map(function ($row) use ($users) {
            $u = $users->get($row->agent_id);
            $name = $u
                ? (trim(($u->firstname ?? '').' '.($u->lastname ?? '')) ?: 'Sin nombre')
                : 'Sin asignar';

            return [
                'id' => $row->id,
                'rating' => $row->rating,
                'comment' => $row->comment,
                'agent_name' => $name,
                'answered_at' => $row->answered_at?->toIso8601String(),
            ];
        })->values()->all();
    }
}
