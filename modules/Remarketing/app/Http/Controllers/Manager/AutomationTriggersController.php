<?php

namespace Modules\Remarketing\Http\Controllers\Manager;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AutomationTriggersController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('remarketing.triggers.view'), 403);

        $hours = max(1, min(720, (int) $request->query('hours', 24)));

        $cutoff = now()->subHours($hours);

        $stats = DB::table('remarketing_automation_triggers_log')
            ->where('triggered_at', '>=', $cutoff)
            ->selectRaw('
                trigger_type,
                COUNT(*) as total,
                SUM(IF(email_sent = 1, 1, 0)) as sent,
                SUM(IF(skip_reason IS NOT NULL, 1, 0)) as skipped,
                COUNT(DISTINCT customer_id) as unique_customers
            ')
            ->groupBy('trigger_type')
            ->orderByDesc('total')
            ->get();

        $recent = DB::table('remarketing_automation_triggers_log as t')
            ->leftJoin('remarketing_customers as c', 'c.id', '=', 't.customer_id')
            ->where('t.triggered_at', '>=', $cutoff)
            ->orderByDesc('t.triggered_at')
            ->limit(100)
            ->select(
                't.id',
                't.trigger_type',
                't.email_sent',
                't.skip_reason',
                't.triggered_at',
                't.context',
                'c.email as customer_email',
                'c.first_name',
                'c.last_name'
            )
            ->get();

        $sentLast24h = DB::table('remarketing_automation_triggers_log')
            ->where('email_sent', 1)
            ->where('triggered_at', '>=', now()->subDay())
            ->count();

        $totalAttempts24h = DB::table('remarketing_automation_triggers_log')
            ->where('triggered_at', '>=', now()->subDay())
            ->count();

        $deliveryRate = $totalAttempts24h > 0
            ? round(($sentLast24h / $totalAttempts24h) * 100, 1)
            : 0;

        $hourlyChart = DB::table('remarketing_automation_triggers_log')
            ->where('triggered_at', '>=', now()->subDay())
            ->selectRaw("DATE_FORMAT(triggered_at, '%Y-%m-%d %H:00') as hour, COUNT(*) as total, SUM(IF(email_sent=1, 1, 0)) as sent")
            ->groupByRaw("DATE_FORMAT(triggered_at, '%Y-%m-%d %H:00')")
            ->orderBy('hour')
            ->get();

        return view('remarketing::manager.triggers.index', [
            'hours' => $hours,
            'stats' => $stats,
            'recent' => $recent,
            'sentLast24h' => $sentLast24h,
            'totalAttempts24h' => $totalAttempts24h,
            'deliveryRate' => $deliveryRate,
            'hourlyChart' => $hourlyChart,
        ]);
    }
}
