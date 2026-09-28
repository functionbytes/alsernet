<?php

namespace Modules\HelpdeskAnalytics\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskLivechat\Models\ChatAttributedSale;

/**
 * Informe de ventas atribuidas al chat web (live commerce, fase 4):
 * ingresos, pedidos, ticket medio y conversión, por día y por agente/bot.
 */
class ChatSalesController extends Controller
{
    private const MAX_RANGE_DAYS = 366;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to'])->endOfDay() : CarbonImmutable::now()->endOfDay();
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from'])->startOfDay() : $to->subDays(29)->startOfDay();
        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            $from = $to->subDays(self::MAX_RANGE_DAYS)->startOfDay();
        }

        $available = class_exists(ChatAttributedSale::class);
        $report = $available ? $this->report($from, $to) : null;

        return view('helpdeskanalytics::chat-sales.index', [
            'from' => $from,
            'to' => $to,
            'available' => $available,
            'report' => $report,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function report(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $base = fn () => ChatAttributedSale::query()->whereBetween('ordered_at', [$from, $to]);

        $totals = $base()
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue, SUM(same_session) AS same_session')
            ->first();
        $orders = (int) ($totals->orders ?? 0);
        $revenue = (float) ($totals->revenue ?? 0);

        $webConversations = Conversation::query()
            ->where('channel', 'web')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $byDay = $base()
            ->selectRaw('DATE(ordered_at) AS day, COUNT(*) AS orders, SUM(total) AS revenue')
            ->groupBy(DB::raw('DATE(ordered_at)'))
            ->orderBy('day')
            ->get();

        $byAgent = $base()
            ->selectRaw('agent_id, COUNT(*) AS orders, SUM(total) AS revenue')
            ->groupBy('agent_id')
            ->orderByDesc('revenue')
            ->get();
        $agentNames = User::query()
            ->whereIn('id', $byAgent->pluck('agent_id')->filter()->all())
            ->get()
            ->mapWithKeys(fn (User $u) => [$u->id => trim((string) ($u->name ?? '')) ?: ('#'.$u->id)]);

        $recent = $base()->latest('ordered_at')->limit(20)->get();

        // Piloto chat propio vs Oct8ne: todos los pedidos con grupo. Como el
        // reparto es aleatorio con % conocido, se compara ingreso por punto de
        // tráfico (ingresos del grupo / % del grupo).
        $pilot = DB::connection('helpdesk')->table('helpdesk_chat_pilot_orders')
            ->whereBetween('ordered_at', [$from, $to])
            ->selectRaw('bucket, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS revenue, MAX(pilot_percent) AS pct')
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket');
        $pilotRows = [];
        foreach (['widget', 'oct8ne'] as $bucket) {
            $row = $pilot->get($bucket);
            $pctWidget = $pilot->max('pct');
            $share = $pctWidget === null ? null : ($bucket === 'widget' ? (int) $pctWidget : 100 - (int) $pctWidget);
            $pilotRows[$bucket] = [
                'orders' => (int) ($row->orders ?? 0),
                'revenue' => (float) ($row->revenue ?? 0),
                'share' => $share,
                'revenue_per_point' => $share ? ((float) ($row->revenue ?? 0)) / $share : null,
            ];
        }

        return [
            'orders' => $orders,
            'revenue' => $revenue,
            'avg_ticket' => $orders > 0 ? $revenue / $orders : 0.0,
            'same_session' => (int) ($totals->same_session ?? 0),
            'web_conversations' => $webConversations,
            'conversion' => $webConversations > 0 ? $orders / $webConversations : null,
            'by_day' => $byDay,
            'by_agent' => $byAgent,
            'agent_names' => $agentNames,
            'recent' => $recent,
            'pilot' => $pilot->isEmpty() ? null : $pilotRows,
        ];
    }
}
