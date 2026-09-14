<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\CsatRating;

class TrendsReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.reports.view');
    }

    public function index(): View
    {
        return view('helpdesk::helpdesk.reports.trends');
    }

    public function data(): JsonResponse
    {
        $payload = Cache::remember('helpdesk:reports:trends', 300, function () {
            $from = now()->subDays(30)->startOfDay();
            $to = now()->endOfDay();

            // Build a complete list of dates
            $dates = [];
            $cursor = $from->copy();
            while ($cursor->lte($to)) {
                $dates[] = $cursor->toDateString();
                $cursor->addDay();
            }

            // Messages received per day (from customers = user_id IS NULL)
            $messagesRaw = ConversationItem::query()
                ->whereNull('user_id')
                ->where('type', 'message')
                ->whereBetween('created_at', [$from, $to])
                ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->groupBy(DB::connection('helpdesk')->raw('DATE(created_at)'))
                ->pluck('cnt', 'day');

            // Conversations closed per day
            $closedRaw = Conversation::query()
                ->whereBetween('closed_at', [$from, $to])
                ->selectRaw('DATE(closed_at) as day, COUNT(*) as cnt')
                ->groupBy(DB::connection('helpdesk')->raw('DATE(closed_at)'))
                ->pluck('cnt', 'day');

            // CSAT average per day
            $csatRaw = CsatRating::query()
                ->whereNotNull('answered_at')
                ->whereBetween('answered_at', [$from, $to])
                ->selectRaw('DATE(answered_at) as day, AVG(rating) as avg_rating')
                ->groupBy(DB::connection('helpdesk')->raw('DATE(answered_at)'))
                ->pluck('avg_rating', 'day');

            // Channel distribution for period
            $channelRaw = Conversation::query()
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('channel')
                ->selectRaw('channel, COUNT(*) as cnt')
                ->groupBy('channel')
                ->pluck('cnt', 'channel');

            $labels = $dates;
            $messages = array_map(fn ($d) => (int) ($messagesRaw[$d] ?? 0), $dates);
            $closed = array_map(fn ($d) => (int) ($closedRaw[$d] ?? 0), $dates);
            $csat = array_map(fn ($d) => round((float) ($csatRaw[$d] ?? 0), 2), $dates);

            return [
                'labels' => $labels,
                'messages' => $messages,
                'closed' => $closed,
                'csat' => $csat,
                'channels' => [
                    'labels' => $channelRaw->keys()->all(),
                    'data' => $channelRaw->values()->all(),
                ],
            ];
        });

        return response()->json($payload);
    }
}
