<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerInsightsService;

/**
 * Manager report: customers most at risk of churn.
 *
 * Risk is derived from the volume of negative-sentiment tags applied to a
 * customer's conversations in the last 90 days combined with the customer's
 * computed health score. No external API key is required — the negative
 * sentiment signal reads tags the sentiment listener has already persisted,
 * mirroring ContactAggregatorService::sentiment().
 */
class AtRiskCustomersReportController extends Controller
{
    public function __construct(
        private readonly CustomerInsightsService $insights,
    ) {
        $this->middleware('can:helpdesk.reports.view');
    }

    /**
     * GET /panel/helpdesk/reports/at-risk
     */
    public function index(): View
    {
        return view('helpdesk::helpdesk.reports.at-risk');
    }

    /**
     * GET /panel/helpdesk/reports/at-risk/data
     *
     * Ranks customers by recent negative-sentiment count (desc) then health
     * score (asc). Limited to 50 rows. Degrades to an empty list when the
     * sentiment tags table is unavailable. Result is cached for 5 minutes —
     * mirrors the caching pattern used by ConversationInboxMetricsService.
     */
    public function data(Request $request): JsonResponse
    {
        if (! $this->sentimentTablesAvailable()) {
            return response()->json(['customers' => []]);
        }

        $customers = Cache::remember(
            'helpdesk:reports:at-risk',
            300,
            fn () => $this->rankedAtRiskCustomers(),
        );

        return response()->json(['customers' => $customers]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rankedAtRiskCustomers(): array
    {
        $rows = DB::connection('helpdesk')
            ->table('helpdesk_conversation_tag_pivot as pivot')
            ->join('helpdesk_conversation_tags as t', 't.id', '=', 'pivot.tag_id')
            ->join('helpdesk_conversations as c', 'c.id', '=', 'pivot.conversation_id')
            ->whereNotNull('c.customer_id')
            ->whereIn('t.slug', ['sentiment-negative', 'sentiment_negative'])
            ->where('pivot.created_at', '>=', now()->subDays(90))
            ->selectRaw('c.customer_id as customer_id, COUNT(*) as negative_count, MAX(pivot.created_at) as last_negative_at')
            ->groupBy('c.customer_id')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Bound the volume before touching Customer/health-score data: only the
        // top 50 rows are ever shown, so there's no reason to fetch customers or
        // compute scores for the rest. Ranked by negative_count only at this
        // stage — health score (secondary tie-break) is not known yet.
        $topRows = $rows->sortByDesc('negative_count')->take(50)->values();

        $customerIds = $topRows->pluck('customer_id')->all();

        // Aislamiento por inbox: un agente sin helpdesk.customers.manage solo ve
        // clientes con los que comparte inbox (los demás quedan fuera del listado).
        $user = auth()->user();
        $customers = Customer::query()
            ->when(
                $user && ! $user->can('helpdesk.customers.manage'),
                fn ($q) => $q->forAgent($user),
            )
            ->whereIn('id', $customerIds)
            ->get(['id', 'name', 'email'])
            ->keyBy('id');

        // Puntuaciones de salud en lote: antes se llamaba a healthScore() por
        // cliente dentro del map (~4 consultas × N clientes → N+1); ahora resuelve
        // los mismos agregados en O(1) consultas para las 50 filas restantes.
        $healthScores = $this->insights->healthScoresFor($customerIds);

        return $topRows
            ->map(function ($row) use ($customers, $healthScores): ?array {
                $customer = $customers->get($row->customer_id);

                if (! $customer) {
                    return null;
                }

                return [
                    'customerId' => (int) $customer->id,
                    'name' => $customer->name ?? 'Sin nombre',
                    'email' => $customer->email,
                    'negativeCount' => (int) $row->negative_count,
                    'healthScore' => $healthScores[$customer->id] ?? 50,
                    'lastNegativeAt' => $row->last_negative_at
                        ? now()->parse($row->last_negative_at)->toIso8601String()
                        : null,
                    'contactUrl' => $this->contactUrl($customer->id),
                ];
            })
            ->filter()
            ->sortBy([
                ['negativeCount', 'desc'],
                ['healthScore', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function contactUrl(int $customerId): ?string
    {
        if (! app('router')->has('contacts.show')) {
            return null;
        }

        return route('contacts.show', $customerId);
    }

    private function sentimentTablesAvailable(): bool
    {
        $schema = Schema::connection('helpdesk');

        return $schema->hasTable('helpdesk_conversation_tag_pivot')
            && $schema->hasTable('helpdesk_conversation_tags');
    }
}
