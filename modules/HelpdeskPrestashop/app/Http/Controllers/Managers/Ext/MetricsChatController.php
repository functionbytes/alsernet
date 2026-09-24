<?php

namespace Modules\HelpdeskPrestashop\Http\Controllers\Managers\Ext;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\HelpdeskPrestashop\Services\Ext\MetricsChatService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * «Métricas del chat · PrestaShop»: KPIs, evolución semanal y ranking por
 * agente de lo que se ha hecho contra la tienda desde el panel, sacado del
 * log de actividad 'helpdeskprestashop'. Exportable a CSV (resumen por
 * agente o detalle de las acciones contadas).
 */
class MetricsChatController extends Controller
{
    private const EXPORT_LIMIT = 10000;

    public function __construct(private readonly MetricsChatService $metrics) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('helpdeskprestashop.metrics.view'), 403);

        [$days, $agentId] = $this->filters($request);
        $summary = $this->metrics->summary($days, $agentId);

        return view('helpdeskprestashop::ext.metrics.index', [
            'days' => $days,
            'periods' => $this->metrics->periods(),
            'agentId' => $agentId,
            'agents' => $this->metrics->agentOptions($days),
            'since' => $this->metrics->since($days),
            'metricDefs' => MetricsChatService::METRICS,
        ] + $summary);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('helpdeskprestashop.metrics.view'), 403);

        [$days, $agentId] = $this->filters($request);
        $kind = $request->query('kind') === 'detail' ? 'detail' : 'agents';
        $filename = 'metricas-chat-prestashop-'.($kind === 'detail' ? 'detalle' : 'agentes').'-'.$days.'d-'.now()->format('Ymd-His').'.csv';

        $write = $kind === 'detail'
            ? fn ($out) => $this->writeDetail($out, $days, $agentId)
            : fn ($out) => $this->writeAgents($out, $days, $agentId);

        return response()->streamDownload(function () use ($write) {
            $out = fopen('php://output', 'w');
            // BOM + ';': Excel en español abre así el CSV con tildes y columnas bien.
            fwrite($out, "\xEF\xBB\xBF");
            $write($out);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Una fila por agente con cada métrica (número e importe) y el total.
     *
     * @param  resource  $out
     */
    private function writeAgents($out, int $days, ?int $agentId): void
    {
        $summary = $this->metrics->summary($days, $agentId);

        $header = ['Agente'];
        foreach (MetricsChatService::METRICS as $meta) {
            $header[] = $meta['label'];
            if ($meta['money']) {
                $header[] = $meta['label'].' · importe (€)';
            }
        }
        $header[] = 'Otras acciones en la tienda';
        $header[] = 'Total de acciones';
        fputcsv($out, $header, ';');

        $line = function (string $name, array $metrics, int $other, int $total) use ($out) {
            $cells = [$name];
            foreach (MetricsChatService::METRICS as $key => $meta) {
                $cells[] = (string) $metrics[$key]['count'];
                if ($meta['money']) {
                    $cells[] = number_format((float) $metrics[$key]['amount'], 2, ',', '');
                }
            }
            $cells[] = (string) $other;
            $cells[] = (string) $total;
            fputcsv($out, array_map(fn ($v) => self::csvCell($v), $cells), ';');
        };

        foreach ($summary['ranking'] as $agent) {
            $line($agent['name'], $agent['metrics'], $agent['other'], $agent['total']);
        }

        $line('Total', $summary['kpis'], $summary['other'], $summary['total']);
    }

    /**
     * Una fila por acción contada en alguna métrica, con su importe si lo
     * guardó.
     *
     * @param  resource  $out
     */
    private function writeDetail($out, int $days, ?int $agentId): void
    {
        fputcsv($out, ['Fecha (UTC)', 'Agente', 'Métrica', 'Acción registrada', 'Importe (€)', 'Pedido', 'Carrito', 'Código', 'Cliente (id)', 'Conversación (id)'], ';');

        foreach ($this->metrics->detailRows($days, $agentId, self::EXPORT_LIMIT) as $row) {
            fputcsv($out, array_map(fn ($v) => self::csvCell($v), [
                $row['at'],
                $row['agent'],
                $row['metric'],
                $row['action'],
                $row['amount'] !== null ? number_format($row['amount'], 2, ',', '') : '',
                is_scalar($row['order']) ? $row['order'] : '',
                is_scalar($row['cart']) ? $row['cart'] : '',
                is_scalar($row['code']) ? $row['code'] : '',
                $row['customer_id'] ?? '',
                is_scalar($row['conversation_id']) ? $row['conversation_id'] : '',
            ]), ';');
        }
    }

    /**
     * Nombres y códigos pueden venir de fuera: una celda que empiece por
     * = + - @ la ejecutaría Excel como fórmula (OWASP CSV injection).
     */
    private static function csvCell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }

    /**
     * @return array{0: int, 1: ?int}
     */
    private function filters(Request $request): array
    {
        $periods = $this->metrics->periods();
        $days = (int) $request->query('days', (string) ($periods[1] ?? $periods[0]));
        if (! in_array($days, $periods, true)) {
            $days = $periods[1] ?? $periods[0];
        }

        $agentId = $request->integer('agent') ?: null;

        return [$days, $agentId];
    }
}
