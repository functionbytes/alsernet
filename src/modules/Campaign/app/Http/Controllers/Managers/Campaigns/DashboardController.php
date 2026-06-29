<?php

namespace Modules\Campaign\Http\Controllers\Managers\Campaigns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Campaign\Models\Automation\Automation2;
use Modules\Campaign\Models\Campaign;
use Modules\Campaign\Models\CampaignMaillist;
use Modules\Campaign\Models\CampaignSubscriber;
use Modules\Campaign\Models\Form;
use Modules\Campaign\Models\Funnel\Funnel;

/**
 * DashboardController — panel con métricas globales del módulo. Portado/adaptado
 * de acellemail (Refactor\DashboardController) a un destino no-SaaS: stats
 * globales en vez de por-cliente, calculadas directamente sobre los modelos.
 */
class DashboardController extends Controller
{
    private const ALLOWED_DAYS = [7, 14, 30, 90];

    private const DEFAULT_DAYS = 30;

    public function index(Request $request)
    {
        $counts = [
            'campaigns' => Campaign::count(),
            'lists' => CampaignMaillist::count(),
            'subscribers' => CampaignSubscriber::count(),
            'forms' => Form::count(),
            'funnels' => Funnel::count(),
            'automations' => Automation2::count(),
        ];

        $email = $this->emailMetrics();
        $sendingTrend = $this->sentByDay(7);
        $recentCampaigns = Campaign::orderByDesc('created_at')->limit(5)->get();

        return view('campaign::manager.dashboard.index', compact('counts', 'email', 'sendingTrend', 'recentCampaigns'));
    }

    public function chartData(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', self::DEFAULT_DAYS);
        $days = in_array($days, self::ALLOWED_DAYS, true) ? $days : self::DEFAULT_DAYS;

        return response()->json(['data' => $this->sentByDay($days)]);
    }

    /** Métricas agregadas de email (sent/opens/clicks/bounces + tasas). */
    private function emailMetrics(): array
    {
        $sent = DB::table('campaign_tracking_logs')->where('status', 'sent')->count();
        $opens = $this->tableCount('campaign_open_logs');
        $clicks = $this->tableCount('campaign_click_logs');
        $bounces = DB::table('campaign_tracking_logs')->where('status', 'bounced')->count();

        return [
            'sent' => $sent,
            'opens' => $opens,
            'clicks' => $clicks,
            'bounces' => $bounces,
            'open_rate' => $sent > 0 ? round($opens * 100 / $sent, 1) : 0.0,
            'click_rate' => $sent > 0 ? round($clicks * 100 / $sent, 1) : 0.0,
            'bounce_rate' => $sent > 0 ? round($bounces * 100 / $sent, 1) : 0.0,
        ];
    }

    private function tableCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Envíos por día de los últimos N días (para el gráfico). */
    private function sentByDay(int $days): array
    {
        $from = Carbon::today()->subDays($days - 1);

        $rows = DB::table('campaign_tracking_logs')
            ->where('status', 'sent')
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $out = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i)->toDateString();
            $out[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
        }

        return $out;
    }
}
