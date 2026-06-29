<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;

class LeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $period = $request->input('period', '7d');

        $since = match ($period) {
            '1d' => now()->subDay(),
            '30d' => now()->subDays(30),
            default => now()->subDays(7),
        };

        $leaderboard = DB::connection('helpdesk')
            ->table('helpdesk_conversations as c')
            ->join('users as u', 'c.assignee_id', '=', 'u.id')
            ->where('c.updated_at', '>=', $since)
            ->whereNotNull('c.assignee_id')
            ->select(
                'u.id',
                DB::raw("CONCAT(u.firstname, ' ', u.lastname) as name"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN c.status_id IN (SELECT id FROM helpdesk_conversation_statuses WHERE is_open = 0) THEN 1 ELSE 0 END) as resolved'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, c.created_at, c.closed_at)) as avg_resolution_minutes'),
            )
            ->groupBy('u.id', 'u.firstname', 'u.lastname')
            ->orderByDesc('resolved')
            ->limit(20)
            ->get();

        return view('helpdesk::managers.leaderboard.index', compact('leaderboard', 'period'));
    }
}
