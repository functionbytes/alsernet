<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\CsatRating;
use Modules\Helpdesk\Services\AgentPresenceService;

class LiveDashboardController extends Controller
{
    public function __construct(
        private readonly AgentPresenceService $presence,
    ) {}

    public function index(): View
    {
        $this->authorize('helpdesk.conversations.view');

        return view('helpdesk::helpdesk.dashboard.live');
    }

    public function metrics(): JsonResponse
    {
        $this->authorize('helpdesk.conversations.view');

        $metrics = Cache::remember('helpdesk:live-dashboard:metrics', 8, fn () => [
            'active_conversations' => $this->activeConversations(),
            'agents_online' => $this->agentsOnline(),
            'messages_today' => $this->messagesToday(),
            'avg_first_response_seconds' => $this->avgFirstResponseSeconds(),
            'csat_avg_today' => $this->csatAvgToday(),
            'open_by_channel' => $this->openByChannel(),
            'queue_pending_jobs' => $this->queuePendingJobs(),
        ]);

        return response()->json($metrics);
    }

    private function activeConversations(): int
    {
        return Conversation::query()
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->count();
    }

    private function agentsOnline(): int
    {
        // Not the SQL `sessions` table: SESSION_DRIVER is redis, so it's never
        // populated — real presence lives in AgentPresenceService's Redis
        // heartbeat instead.
        return count($this->presence->getOnlineAgents());
    }

    private function messagesToday(): int
    {
        return ConversationItem::query()
            ->whereBetween('created_at', [today(), today()->endOfDay()])
            ->count();
    }

    private function avgFirstResponseSeconds(): int
    {
        $avg = Conversation::query()
            ->whereBetween('closed_at', [today(), today()->endOfDay()])
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_seconds')
            ->value('avg_seconds');

        return (int) round($avg ?? 0);
    }

    private function csatAvgToday(): float
    {
        return round(
            CsatRating::query()
                ->whereBetween('answered_at', [today(), today()->endOfDay()])
                ->avg('rating') ?? 0.0,
            2
        );
    }

    private function openByChannel(): array
    {
        return Conversation::query()
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->selectRaw('channel, COUNT(*) as total')
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->toArray();
    }

    private function queuePendingJobs(): int
    {
        return (int) DB::table('jobs')->count();
    }
}
