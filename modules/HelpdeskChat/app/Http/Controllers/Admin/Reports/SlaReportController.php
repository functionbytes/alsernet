<?php

namespace Modules\HelpdeskChat\Http\Controllers\Admin\Reports;

use Illuminate\Http\Request;
use Modules\HelpdeskChat\Http\Controllers\Controller;
use Modules\HelpdeskChat\Models\Conversations\Conversation;
use Modules\HelpdeskChat\Models\Sla\SlaPolicy;

class SlaReportController extends Controller
{
    /**
     * Display SLA overview dashboard.
     */
    public function index(Request $request)
    {
        $accountId = auth()->user()->account_id;
        $period = $request->input('period', '7'); // Default 7 days

        // Get date range
        $startDate = now()->subDays($period);

        // Total conversation with SLA
        $totalWithSla = Conversation::where('account_id', $accountId)
            ->whereNotNull('sla_policy_id')
            ->where('created_at', '>=', $startDate)
            ->count();

        // First response metrics
        $firstResponseStats = Conversation::where('account_id', $accountId)
            ->whereNotNull('sla_policy_id')
            ->whereNotNull('first_response_at')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN first_response_sla_breached = 1 THEN 1 ELSE 0 END) as breached,
                SUM(CASE WHEN first_response_sla_breached = 0 THEN 1 ELSE 0 END) as met
            ')
            ->first();

        // Resolution metrics
        $resolutionStats = Conversation::where('account_id', $accountId)
            ->whereNotNull('sla_policy_id')
            ->whereNotNull('resolved_at')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN resolution_sla_breached = 1 THEN 1 ELSE 0 END) as breached,
                SUM(CASE WHEN resolution_sla_breached = 0 THEN 1 ELSE 0 END) as met
            ')
            ->first();

        // Calculate breach rates
        $firstResponseBreachRate = $firstResponseStats->total > 0
            ? round(($firstResponseStats->breached / $firstResponseStats->total) * 100, 1)
            : 0;

        $resolutionBreachRate = $resolutionStats->total > 0
            ? round(($resolutionStats->breached / $resolutionStats->total) * 100, 1)
            : 0;

        // Conversations approaching SLA breach (within 1 hour)
        $approaching = Conversation::where('account_id', $accountId)
            ->whereNotNull('sla_policy_id')
            ->where('status', '!=', 'resolved')
            ->whereNull('first_response_at')
            ->with(['contact', 'inbox', 'slaPolicy'])
            ->get()
            ->filter(function ($conv) {
                if (! $conv->slaPolicy || ! $conv->slaPolicy->first_response_time_minutes) {
                    return false;
                }
                $elapsed = now()->diffInMinutes($conv->created_at);
                $target = $conv->slaPolicy->first_response_time_minutes;
                $remaining = $target - $elapsed;

                return $remaining > 0 && $remaining <= 60; // Within 1 hour
            });

        // Performance by agent
        $agentPerformance = Conversation::where('helpdesk_conversations.account_id', $accountId)
            ->whereNotNull('helpdesk_conversations.sla_policy_id')
            ->whereNotNull('helpdesk_conversations.assignee_id')
            ->where('helpdesk_conversations.created_at', '>=', $startDate)
            ->join('users', 'helpdesk_conversations.assignee_id', '=', 'users.id')
            ->selectRaw('
                users.id,
                users.name,
                COUNT(*) as total_conversations,
                SUM(CASE WHEN helpdesk_conversations.first_response_sla_breached = 0 AND helpdesk_conversations.first_response_at IS NOT NULL THEN 1 ELSE 0 END) as first_response_met,
                SUM(CASE WHEN helpdesk_conversations.first_response_sla_breached = 1 THEN 1 ELSE 0 END) as first_response_breached,
                SUM(CASE WHEN helpdesk_conversations.resolution_sla_breached = 0 AND helpdesk_conversations.resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolution_met,
                SUM(CASE WHEN helpdesk_conversations.resolution_sla_breached = 1 THEN 1 ELSE 0 END) as resolution_breached
            ')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_conversations')
            ->limit(10)
            ->get();

        // Daily trend (last 7 days)
        $dailyTrend = Conversation::where('account_id', $accountId)
            ->whereNotNull('sla_policy_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN first_response_sla_breached = 1 THEN 1 ELSE 0 END) as breached
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Active SLA policies
        $activePolicies = SlaPolicy::where('account_id', $accountId)
            ->where('is_active', true)
            ->withCount('conversations')
            ->get();

        return view('helpdeskchat::admin.reports.sla.index', compact(
            'totalWithSla',
            'firstResponseStats',
            'resolutionStats',
            'firstResponseBreachRate',
            'resolutionBreachRate',
            'approaching',
            'agentPerformance',
            'dailyTrend',
            'activePolicies',
            'period'
        ));
    }

    /**
     * Get SLA widget data for dashboard.
     */
    public function widget(Request $request)
    {
        $accountId = auth()->user()->account_id;

        // Conversations approaching breach (next hour)
        $approaching = Conversation::where('account_id', $accountId)
            ->whereNotNull('sla_policy_id')
            ->where('status', '!=', 'resolved')
            ->whereNull('first_response_at')
            ->with(['contact', 'slaPolicy'])
            ->get()
            ->filter(function ($conv) {
                if (! $conv->slaPolicy || ! $conv->slaPolicy->first_response_time_minutes) {
                    return false;
                }
                $elapsed = now()->diffInMinutes($conv->created_at);
                $target = $conv->slaPolicy->first_response_time_minutes;
                $remaining = $target - $elapsed;

                return $remaining > 0 && $remaining <= 60;
            })
            ->take(5);

        // Today's breaches
        $todayBreaches = Conversation::where('account_id', $accountId)
            ->whereNotNull('sla_policy_id')
            ->whereDate('created_at', today())
            ->where(function ($query) {
                $query->where('first_response_sla_breached', true)
                    ->orWhere('resolution_sla_breached', true);
            })
            ->count();

        return response()->json([
            'approaching' => $approaching->map(function ($conv) {
                $elapsed = now()->diffInMinutes($conv->created_at);
                $target = $conv->slaPolicy->first_response_time_minutes;
                $remaining = $target - $elapsed;

                return [
                    'id' => $conv->id,
                    'contact_name' => $conv->contact->name,
                    'remaining_minutes' => max(0, $remaining),
                ];
            }),
            'today_breaches' => $todayBreaches,
        ]);
    }
}
