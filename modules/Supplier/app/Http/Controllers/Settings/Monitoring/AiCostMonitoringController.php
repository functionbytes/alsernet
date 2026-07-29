<?php

namespace Modules\Supplier\Http\Controllers\Settings\Monitoring;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Budget\UpdateBudgetRequest;
use Modules\Supplier\Http\Requests\Budget\UpdateBudgetsRequest;
use Modules\Supplier\Models\Ai\AiBudget;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Ai\AiCost;
use Modules\Supplier\Models\Supplier\Supplier;

class AiCostMonitoringController extends Controller
{
    public function index(): View
    {
        $pageTitle = 'Monitoreo de IA';
        $breadcrumb = 'Configuración / Proveedores / Monitoreo IA';

        $budgets = AiBudget::query()
            ->select(['id', 'provider', 'monthly_limit', 'daily_limit', 'alert_threshold_pct', 'is_active', 'block_on_exceed'])
            ->get()
            ->keyBy('provider');

        $monthlyStats = $this->getMonthlyStats();
        $recentLogs = AiCost::query()
            ->with('supplier')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $funnelStats = AiContent::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $contentMetrics = $this->getContentMetrics();

        $budgetWarnings = $this->buildBudgetWarnings($budgets);
        $budgetWarning  = count($budgetWarnings) > 0;

        return view('supplier::settings.views.monitoring.index', compact(
            'pageTitle', 'breadcrumb', 'budgets', 'monthlyStats', 'recentLogs', 'funnelStats', 'contentMetrics',
            'budgetWarning', 'budgetWarnings'
        ));
    }

    public function usageStats(Request $request): JsonResponse
    {
        $days = max(1, min((int) $request->get('days', 7), 365));
        $byModel = $request->boolean('by_model');

        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $dailyCosts = AiCost::getDailyCosts($from->toDateTimeString(), $to->toDateTimeString());

        $labels = [];
        $period = Carbon::parse($from);
        while ($period->lte($to)) {
            $labels[] = $period->toDateString();
            $period->addDay();
        }

        if (! $byModel) {
            $indexed = collect($dailyCosts)->keyBy('date');
            $data = array_map(
                fn (string $d) => (float) ($indexed->get($d)['total_cost'] ?? 0),
                $labels
            );

            return response()->json([
                'success' => true,
                'labels' => $labels,
                'datasets' => [
                    'total' => [
                        'label' => 'Costo total',
                        'data' => $data,
                    ],
                ],
            ]);
        }

        $byModelData = AiCost::query()
            ->selectRaw('DATE(created_at) as date, model, SUM(COALESCE(input_cost,0)+COALESCE(output_cost,0)+COALESCE(web_search_cost,0)) as total_cost')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date', 'model')
            ->get()
            ->groupBy('model');

        $datasets = [];
        foreach ($byModelData as $model => $rows) {
            $indexed = $rows->keyBy(fn ($r) => (string) $r->date);
            $datasets[$model] = [
                'label' => $model,
                'data' => array_map(
                    fn (string $d) => (float) ($indexed->get($d)?->total_cost ?? 0),
                    $labels
                ),
            ];
        }

        return response()->json([
            'success' => true,
            'labels' => $labels,
            'datasets' => $datasets,
        ]);
    }

    public function logs(Request $request): View
    {
        $pageTitle = 'Logs de consumo IA';
        $breadcrumb = 'Configuración / Proveedores / Monitoreo IA / Logs';

        $query = AiCost::query()->with('supplier');

        if ($model = $request->input('model')) {
            $query->byModel($model);
        }

        if ($operation = $request->input('operation')) {
            $query->byOperation($operation);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->bySupplier((int) $supplierId);
        }

        if ($from = $request->input('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }

        if ($to = $request->input('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('model', 'LIKE', "%{$search}%")
                    ->orWhere('operation_type', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        $suppliers = Supplier::orderBy('label')->get(['id', 'label']);
        $models = AiCost::query()->distinct()->orderBy('model')->pluck('model');
        $operations = AiCost::query()->distinct()->orderBy('operation_type')->pluck('operation_type');

        return view('supplier::settings.views.monitoring.logs', compact(
            'pageTitle', 'breadcrumb', 'logs', 'suppliers', 'models', 'operations'
        ));
    }

    public function updateBudget(UpdateBudgetRequest $request): RedirectResponse
    {
        $data = $request->validated();

        AiBudget::updateOrCreate(
            ['provider' => $data['provider']],
            $data
        );

        return redirect()
            ->route('settings.suppliers.monitoring.index')
            ->with('success', 'Presupuesto actualizado correctamente.');
    }

    public function updateBudgets(UpdateBudgetsRequest $request): RedirectResponse
    {
        $budgets = $request->validated()['budgets'];

        foreach (['openai', 'anthropic'] as $provider) {
            if (! isset($budgets[$provider])) {
                continue;
            }

            $data = $budgets[$provider];

            AiBudget::updateOrCreate(
                ['provider' => $provider],
                [
                    'monthly_limit'       => $data['monthly_limit'],
                    'daily_limit'         => $data['daily_limit'] ?? null,
                    'alert_threshold_pct' => $data['alert_threshold_pct'] ?? 80,
                    'is_active'           => $data['is_active'],
                    'block_on_exceed'     => $data['block_on_exceed'],
                ]
            );
        }

        return redirect()
            ->route('settings.suppliers.monitoring.index')
            ->with('success', 'Presupuestos actualizados correctamente.');
    }

    public function topPrompts(Request $request): JsonResponse
    {
        $month = $request->get('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $data = Cache::remember('supplier:ai:top_prompts:'.$month, 300, function () use ($month) {
            return AiContent::query()
                ->join('supplier_prompts', 'supplier_ai_contents.prompt_id', '=', 'supplier_prompts.id')
                ->whereNotNull('supplier_ai_contents.prompt_id')
                ->whereYear('supplier_ai_contents.created_at', substr($month, 0, 4))
                ->whereMonth('supplier_ai_contents.created_at', substr($month, 5, 2))
                ->selectRaw('supplier_ai_contents.prompt_id, supplier_prompts.label as prompt_label, COUNT(*) as uses, AVG(JSON_EXTRACT(supplier_ai_contents.generation_metadata, \'$.tokens.total\')) as avg_tokens')
                ->groupBy('supplier_ai_contents.prompt_id', 'supplier_prompts.label')
                ->orderByDesc('uses')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'prompt_id' => $r->prompt_id,
                    'prompt_label' => $r->prompt_label ?? 'Desconocido',
                    'uses' => (int) $r->uses,
                    'avg_tokens' => round($r->avg_tokens ?? 0),
                ]);
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function budgetAlerts(): JsonResponse
    {
        $alerts = Cache::remember(
            'supplier:ai:budget_alerts',
            300,
            fn () => $this->buildBudgetAlerts(),
        );

        return response()->json(['success' => true, 'alerts' => $alerts, 'count' => count($alerts)]);
    }

    /**
     * Build server-side budget warnings for the page banner (threshold >= 80%).
     *
     * @param  \Illuminate\Support\Collection<string, AiBudget>  $budgets  keyed by provider
     * @return array<int, array{level: string, provider: string, monthly_pct: float, daily_pct: float, monthly_usage: float, monthly_limit: float, daily_usage: float, daily_limit: float|null, blocked: bool}>
     */
    private function buildBudgetWarnings(\Illuminate\Support\Collection $budgets): array
    {
        $warnings = [];

        foreach ($budgets as $budget) {
            if (! $budget->is_active) {
                continue;
            }

            $monthlyPct = $budget->usagePercentage();
            $dailyPct   = $budget->dailyUsagePercentage();

            if ($monthlyPct < 80 && $dailyPct < 80) {
                continue;
            }

            $level = ($monthlyPct >= 100 || $dailyPct >= 100) ? 'danger'
                : (($monthlyPct >= 90 || $dailyPct >= 90) ? 'danger' : 'warning');

            $warnings[] = [
                'level'          => $level,
                'provider'       => $budget->provider,
                'monthly_pct'    => $monthlyPct,
                'daily_pct'      => $dailyPct,
                'monthly_usage'  => $budget->currentMonthUsage(),
                'monthly_limit'  => (float) $budget->monthly_limit,
                'daily_usage'    => $budget->currentDayUsage(),
                'daily_limit'    => $budget->daily_limit ? (float) $budget->daily_limit : null,
                'blocked'        => $budget->block_on_exceed && ($budget->isExceeded() || $budget->isDailyExceeded()),
            ];
        }

        return $warnings;
    }

    /**
     * Build the active-budget alert list.
     *
     * @return array<int, array{level: string, message: string, budget_id: int}>
     */
    private function buildBudgetAlerts(): array
    {
        $alerts = [];
        $daysLeft = now()->daysInMonth - now()->day;

        foreach (AiBudget::where('is_active', true)->get() as $budget) {
            $pct = $budget->usagePercentage();

            if ($pct >= 90) {
                $alerts[] = [
                    'level' => 'danger',
                    'message' => "Presupuesto {$budget->provider}: {$pct}% consumido — límite inminente",
                    'budget_id' => $budget->id,
                ];
            } elseif ($pct >= 70) {
                $alerts[] = [
                    'level' => 'warning',
                    'message' => "Presupuesto {$budget->provider}: {$pct}% consumido",
                    'budget_id' => $budget->id,
                ];
            }

            if ($budget->dailyUsagePercentage() <= 0 || $daysLeft <= 0) {
                continue;
            }

            $monthUsage = $budget->currentMonthUsage();
            $projected = $monthUsage + ($monthUsage / max(now()->day, 1)) * $daysLeft;

            if ($projected > $budget->monthly_limit * 1.1) {
                $alerts[] = [
                    'level' => 'warning',
                    'message' => "Presupuesto {$budget->provider}: proyección excede límite mensual en {$daysLeft} días",
                    'budget_id' => $budget->id,
                ];
            }
        }

        return $alerts;
    }

    private function getMonthlyStats(): array
    {
        return Cache::remember(
            'supplier:ai:monthly_stats:'.now()->format('Y-m-d-H'),
            now()->addMinutes(5),
            fn () => $this->buildMonthlyStats(),
        );
    }

    private function buildMonthlyStats(): array
    {
        $from = now()->startOfMonth()->toDateTimeString();
        $to = now()->endOfMonth()->toDateTimeString();

        $prevFrom = now()->subMonth()->startOfMonth()->toDateTimeString();
        $prevTo = now()->subMonth()->endOfMonth()->toDateTimeString();

        $totalCost = AiCost::getTotalCostForPeriod($from, $to);
        $prevCost = AiCost::getTotalCostForPeriod($prevFrom, $prevTo);
        $totalTokens = AiCost::getTotalTokensForPeriod($from, $to);

        $requestCount = AiCost::whereBetween('created_at', [$from, $to])->count();
        $prevRequestCount = AiCost::whereBetween('created_at', [$prevFrom, $prevTo])->count();

        $avgLatency = AiCost::getAverageLatency($from, $to);

        $costByModel = AiCost::getCostsByModel($from, $to);
        $costByOperation = AiCost::getCostsByOperation($from, $to);

        $modelDistribution = [];
        foreach ($costByModel as $row) {
            $modelDistribution[$row['model']] = [
                'label' => $row['model'],
                'cost' => (float) $row['total_cost'],
                'count' => (int) $row['requests'],
                'tokens' => (int) $row['total_tokens'],
            ];
        }

        return [
            'total_cost' => $totalCost,
            'total_tokens' => $totalTokens,
            'request_count' => $requestCount,
            'avg_latency_ms' => $avgLatency ? (int) round($avgLatency) : 0,
            'cost_change_pct' => $this->percentChange($prevCost, $totalCost),
            'request_change_pct' => $this->percentChange($prevRequestCount, $requestCount),
            'model_distribution' => $modelDistribution,
            'operation_distribution' => $costByOperation,
        ];
    }

    private function percentChange(float|int $previous, float|int $current): ?float
    {
        if ($previous <= 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getContentMetrics(): array
    {
        return Cache::remember(
            'supplier:ai:content_metrics:'.now()->format('Y-m-d-H'),
            now()->addMinutes(5),
            fn () => $this->buildContentMetrics(),
        );
    }

    private function buildContentMetrics(): array
    {
        $generatedCount = AiContent::query()
            ->whereIn('status', [
                AiContent::STATUS_PENDING_VALIDATION,
                AiContent::STATUS_IN_REVIEW,
                AiContent::STATUS_NEEDS_REVISION,
                AiContent::STATUS_VALIDATED,
                AiContent::STATUS_PUBLISHED,
            ])
            ->count();

        $noSourcesCount = AiContent::query()
            ->whereIn('status', [
                AiContent::STATUS_PENDING_VALIDATION,
                AiContent::STATUS_IN_REVIEW,
                AiContent::STATUS_NEEDS_REVISION,
                AiContent::STATUS_VALIDATED,
                AiContent::STATUS_PUBLISHED,
            ])
            ->where(function ($q) {
                $q->whereNull('sources_used')
                    ->orWhere('sources_used', '[]')
                    ->orWhere('sources_used', '');
            })
            ->count();

        $monthFrom = now()->startOfMonth()->toDateTimeString();
        $monthTo = now()->endOfMonth()->toDateTimeString();
        $monthlyCost = AiCost::getTotalCostForPeriod($monthFrom, $monthTo);

        $dayFrom = today()->startOfDay()->toDateTimeString();
        $dayTo = today()->endOfDay()->toDateTimeString();
        $dailyCost = AiCost::getTotalCostForPeriod($dayFrom, $dayTo);

        return [
            'generated_count'  => $generatedCount,
            'no_sources_count' => $noSourcesCount,
            'monthly_cost'     => $monthlyCost,
            'daily_cost'       => $dailyCost,
        ];
    }
}
