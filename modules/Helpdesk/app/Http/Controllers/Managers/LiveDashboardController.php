<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Models\CsatRating;

class LiveDashboardController extends Controller
{
    public function index(): View
    {
        $this->authorize('helpdesk.conversations.view');

        return view('helpdesk::managers.dashboard.live');
    }

    public function metrics(): JsonResponse
    {
        $this->authorize('helpdesk.conversations.view');

        return response()->json([
            'active_conversations' => $this->activeConversations(),
            'agents_online' => $this->agentsOnline(),
            'messages_today' => $this->messagesToday(),
            'avg_first_response_seconds' => $this->avgFirstResponseSeconds(),
            'csat_avg_today' => $this->csatAvgToday(),
            'open_by_channel' => $this->openByChannel(),
            'queue_pending_jobs' => $this->queuePendingJobs(),
        ]);
    }

    private function activeConversations(): int
    {
        return Conversation::query()
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->count();
    }

    private function agentsOnline(): int
    {
        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->distinct('user_id')
            ->count('user_id');
    }

    private function messagesToday(): int
    {
        return ConversationItem::query()
            ->whereDate('created_at', today())
            ->count();
    }

    private function avgFirstResponseSeconds(): int
    {
        $avg = Conversation::query()
            ->whereDate('closed_at', today())
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_seconds')
            ->value('avg_seconds');

        return (int) round($avg ?? 0);
    }

    private function csatAvgToday(): float
    {
        return round(
            CsatRating::query()
                ->whereDate('answered_at', today())
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
