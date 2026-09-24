<?php

namespace Modules\HelpdeskErp\Http\Controllers\Managers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HelpdeskErp\Services\ErpAdmin\ErpAdminMetricsService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * «Métricas de Gestión» (Ajustes → Helpdesk · Gestión (ERP)): uso del panel
 * de Gestión en el chat por los agentes y tiempos de respuesta del manager.
 * Solo lectura de helpdesk_erp_metrics_events.
 */
class ErpAdminMetricsController extends Controller
{
    public const PERMISSION = 'helpdeskerp.metrics.view';

    public function __construct(
        private readonly ErpAdminMetricsService $metrics,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(self::PERMISSION), 403);

        [$days, $agentId] = $this->filters($request);
        $ready = $this->metrics->ready();

        return view('helpdeskerp::admin.metrics', [
            'ready' => $ready,
            'days' => $days,
            'agentId' => $agentId,
            'periods' => $this->metrics->periods(),
            'since' => $this->metrics->since($days),
            'agents' => $ready ? $this->metrics->agentOptions($days) : [],
            'kpis' => $ready ? $this->metrics->kpis($days, $agentId) : null,
            'daily' => $ready ? $this->metrics->daily($days, $agentId) : [],
            'sections' => $ready ? $this->metrics->sections($days, $agentId) : [],
            'resources' => $ready ? $this->metrics->managerResources($days, $agentId) : [],
            'agentRows' => $ready ? $this->metrics->agents($days, $agentId) : [],
            'snapshot' => $this->metrics->linkSnapshot(),
            'enabled' => (bool) config('helpdeskErp.ext.admin.metrics.enabled', true),
            'retention' => (int) config('helpdeskErp.ext.admin.metrics.retention_days', 90),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can(self::PERMISSION), 403);

        [$days, $agentId] = $this->filters($request);
        abort_unless($this->metrics->ready(), 404);

        $filename = 'metricas-gestion-'.$days.'d'.($agentId ? '-agente'.$agentId : '').'-'.now()->format('Ymd-His').'.csv';
        $metrics = $this->metrics;

        return response()->streamDownload(function () use ($metrics, $days, $agentId) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Día', 'Agente (id)', 'Agente', 'Tipo', 'Sección', 'Estado', 'Eventos', 'Bloqueados vistos', 'Media (ms)', 'Máximo (ms)'], ';', '"', '');

            $buffer = [];
            $flush = function () use (&$buffer, $out, $metrics) {
                $names = $metrics->userNames(array_map(fn ($r) => (int) $r->user_id, $buffer));
                foreach ($buffer as $r) {
                    fputcsv($out, array_map(fn ($v) => self::csvCell($v), [
                        (string) $r->d,
                        $r->user_id,
                        $r->user_id ? ($names[(int) $r->user_id] ?? '') : '',
                        ErpAdminMetricsService::KIND_LABELS[$r->kind] ?? $r->kind,
                        $r->section,
                        $r->state,
                        (int) $r->n,
                        (int) $r->blocked,
                        $r->avg_ms !== null ? (int) round((float) $r->avg_ms) : '',
                        $r->max_ms,
                    ]), ';', '"', '');
                }
                $buffer = [];
            };

            foreach ($metrics->exportRows($days, $agentId) as $row) {
                $buffer[] = $row;
                if (count($buffer) >= 500) {
                    $flush();
                }
            }
            $flush();

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: int, 1: ?int}
     */
    private function filters(Request $request): array
    {
        $periods = $this->metrics->periods();
        $days = (int) $request->query('days', (string) $this->metrics->defaultPeriod());
        if (! in_array($days, $periods, true)) {
            $days = $this->metrics->defaultPeriod();
        }

        $agent = $request->query('agent');
        $agentId = is_string($agent) && ctype_digit($agent) && (int) $agent > 0 ? (int) $agent : null;

        return [$days, $agentId];
    }

    /**
     * Neutraliza fórmulas al abrir el CSV en una hoja de cálculo.
     */
    private static function csvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
