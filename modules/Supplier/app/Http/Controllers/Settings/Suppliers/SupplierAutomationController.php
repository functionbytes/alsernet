<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Supplier\Http\Requests\Automation\BulkWorkflowActionRequest;
use Modules\Supplier\Models\Automation\AutomationAlert;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationTrigger;
use Modules\Supplier\Models\Automation\AutomationWorkflow;

class SupplierAutomationController extends Controller
{
    /**
     * Display the automation dashboard with paginated lists
     */
    public function index(Request $request): View
    {
        $pageTitle = 'Automatización de Proveedores';
        $breadcrumb = 'Configuración / Proveedores / Automatización';

        $tab = $request->get('tab', 'workflows');
        $stats = $this->getDashboardStats();

        // ── Workflows ───────────────────────────────────────────────────────
        $wfSearch = $request->get('wf_search');
        $wfType = $request->get('wf_type');
        $wfStatus = $request->get('wf_status');

        $workflows = AutomationWorkflow::query()
            ->with('creator')
            ->when($wfSearch, fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$wfSearch}%")
                ->orWhere('description', 'like', "%{$wfSearch}%")))
            ->when($wfType !== null && $wfType !== '', fn ($q) => $q->where('workflow_type', $wfType))
            ->when($wfStatus !== null && $wfStatus !== '', fn ($q) => $q->where('is_active', (bool) $wfStatus))
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'wpage')
            ->appends($request->except('wpage'));

        // ── Executions ──────────────────────────────────────────────────────
        $exSearch = $request->get('ex_search');
        $exStatus = $request->get('ex_status');
        $exTrigger = $request->get('ex_trigger');

        $executions = AutomationExecution::query()
            ->with(['workflow', 'supplier'])
            ->when($exSearch, fn ($q) => $q->where(fn ($e) => $e
                ->whereHas('workflow', fn ($w) => $w->where('name', 'like', "%{$exSearch}%"))
                ->orWhereHas('supplier', fn ($s) => $s->where('label', 'like', "%{$exSearch}%"))))
            ->when($exStatus !== null && $exStatus !== '', fn ($q) => $q->where('status', $exStatus))
            ->when($exTrigger !== null && $exTrigger !== '', fn ($q) => $q->where('trigger_type', $exTrigger))
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'epage')
            ->appends($request->except('epage'));

        // ── Triggers ────────────────────────────────────────────────────────
        $trSearch = $request->get('tr_search');
        $trType = $request->get('tr_type');
        $trStatus = $request->get('tr_status');

        $triggers = AutomationTrigger::query()
            ->with('workflow')
            ->when($trSearch, fn ($q) => $q->where(fn ($t) => $t
                ->where('name', 'like', "%{$trSearch}%")
                ->orWhere('description', 'like', "%{$trSearch}%")))
            ->when($trType !== null && $trType !== '', fn ($q) => $q->where('trigger_type', $trType))
            ->when($trStatus !== null && $trStatus !== '', fn ($q) => $q->where('is_enabled', (bool) $trStatus))
            ->orderByDesc('is_enabled')
            ->orderBy('name')
            ->paginate(10, ['*'], 'tpage')
            ->appends($request->except('tpage'));

        // ── Alerts ──────────────────────────────────────────────────────────
        $alSearch = $request->get('al_search');
        $alSeverity = $request->get('al_severity');
        $alType = $request->get('al_type');
        $alAck = $request->get('al_ack');

        $alerts = AutomationAlert::query()
            ->when($alSearch, fn ($q) => $q->where(fn ($a) => $a
                ->where('title', 'like', "%{$alSearch}%")
                ->orWhere('message', 'like', "%{$alSearch}%")))
            ->when($alSeverity !== null && $alSeverity !== '', fn ($q) => $q->where('severity', $alSeverity))
            ->when($alType !== null && $alType !== '', fn ($q) => $q->where('alert_type', $alType))
            ->when($alAck === '1', fn ($q) => $q->whereNotNull('acknowledged_at'))
            ->when($alAck === '0', fn ($q) => $q->whereNull('acknowledged_at'))
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'apage')
            ->appends($request->except('apage'));

        // ── Distinct option lists for filter selects ────────────────────────
        $workflowTypes = AutomationWorkflow::query()->whereNotNull('workflow_type')->distinct()->orderBy('workflow_type')->pluck('workflow_type');
        $triggerTypes = AutomationTrigger::query()->whereNotNull('trigger_type')->distinct()->orderBy('trigger_type')->pluck('trigger_type');
        $execTriggerTypes = AutomationExecution::query()->whereNotNull('trigger_type')->distinct()->orderBy('trigger_type')->pluck('trigger_type');
        $alertTypes = AutomationAlert::query()->whereNotNull('alert_type')->distinct()->orderBy('alert_type')->pluck('alert_type');

        return view('supplier::settings.views.automation.index', compact(
            'pageTitle', 'breadcrumb', 'stats', 'tab',
            'workflows', 'executions', 'triggers', 'alerts',
            'workflowTypes', 'triggerTypes', 'execTriggerTypes', 'alertTypes',
            'wfSearch', 'wfType', 'wfStatus',
            'exSearch', 'exStatus', 'exTrigger',
            'trSearch', 'trType', 'trStatus',
            'alSearch', 'alSeverity', 'alType', 'alAck',
        ));
    }

    /**
     * Get automation health metrics (JSON)
     */
    public function getHealthMetrics(): JsonResponse
    {
        try {
            $stats = $this->getDashboardStats();

            $totalExecutions = $stats['total_executions_today'];
            $failedExecutions = $stats['failed_executions_today'];
            $failureRate = $totalExecutions > 0 ? ($failedExecutions / $totalExecutions) * 100 : 0;

            $systemHealth = match (true) {
                $failureRate > 50 => 'critical',
                $failureRate > 20 => 'warning',
                default => 'healthy',
            };

            return response()->json([
                'success' => true,
                'data' => [
                    'active_workflows' => $stats['active_workflows'],
                    'pending_executions' => $stats['pending_executions'],
                    'failed_executions' => $failedExecutions,
                    'system_health' => $systemHealth,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting health metrics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las métricas de salud',
            ], 500);
        }
    }

    /**
     * Get cached dashboard stats (60s TTL — near real-time for a dashboard).
     *
     * @return array{active_workflows: int, total_executions_today: int, failed_executions_today: int, pending_executions: int}
     */
    private function getDashboardStats(): array
    {
        return Cache::remember('automation.dashboard.stats', 60, function () {
            return [
                'active_workflows' => AutomationWorkflow::where('is_active', true)->count(),
                'total_executions_today' => AutomationExecution::whereDate('created_at', today())->count(),
                'failed_executions_today' => AutomationExecution::whereDate('created_at', today())->where('status', 'failed')->count(),
                'pending_executions' => AutomationExecution::where('status', 'pending')->count(),
            ];
        });
    }

    /**
     * Invalidate the cached dashboard stats.
     */
    public static function forgetDashboardStats(): void
    {
        Cache::forget('automation.dashboard.stats');
    }

    /**
     * Bulk action on workflows
     */
    public function bulkAction(BulkWorkflowActionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $uids = $validated['uids'];

        $count = match ($action) {
            'delete' => AutomationWorkflow::whereIn('uid', $uids)->delete(),
            'enable' => AutomationWorkflow::whereIn('uid', $uids)->update(['is_active' => true]),
            'disable' => AutomationWorkflow::whereIn('uid', $uids)->update(['is_active' => false]),
        };

        self::forgetDashboardStats();

        $labels = [
            'delete' => 'eliminado(s)',
            'enable' => 'activado(s)',
            'disable' => 'desactivado(s)',
        ];

        return response()->json([
            'message' => "{$count} workflow(s) {$labels[$action]}.",
            'count' => $count,
        ]);
    }
}
