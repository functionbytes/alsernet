<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        // Permiso de reportes (consistente con los demás controllers de reportes),
        // no el de ver clientes: son estadísticas de rendimiento de agentes.
        abort_unless($request->user()?->can('helpdesk.reports.view'), 403);

        $period = $request->input('period', '7d');

        $leaderboard = Cache::remember("helpdesk:leaderboard:{$period}", 60, fn () => $this->fetchLeaderboard($period));

        return view('helpdesk::helpdesk.leaderboard.index', compact('leaderboard', 'period'));
    }

    private function fetchLeaderboard(string $period): Collection
    {
        $since = match ($period) {
            '1d' => now()->subDay(),
            '30d' => now()->subDays(30),
            default => now()->subDays(7),
        };

        return DB::connection('helpdesk')
            ->table('helpdesk_conversations as c')
            ->join('users as u', 'c.assignee_id', '=', 'u.id')
            ->leftJoin('helpdesk_conversation_statuses as s', function ($join) {
                $join->on('s.id', '=', 'c.status_id')->where('s.is_open', '=', 0);
            })
            ->where('c.updated_at', '>=', $since)
            ->whereNotNull('c.assignee_id')
            ->select(
                'u.id',
                DB::raw("CONCAT(u.firstname, ' ', u.lastname) as name"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(s.id IS NOT NULL) as resolved'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, c.created_at, c.closed_at)) as avg_resolution_minutes'),
            )
            ->groupBy('u.id', 'u.firstname', 'u.lastname')
            ->orderByDesc('resolved')
            ->limit(20)
            ->get();
    }
}
