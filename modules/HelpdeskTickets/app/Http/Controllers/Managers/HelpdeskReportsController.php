<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Services\Exports\CsvStreamExporter;
use Modules\HelpdeskTickets\Services\Exports\TicketsExporter;
use Modules\HelpdeskTickets\Services\OpsHealthService;
use Modules\HelpdeskTickets\Services\TicketReportsService;
use Modules\HelpdeskTickets\Support\ReportsCache;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Helpdesk reports dashboard at /panel/helpdesk/reports — owned by HelpdeskTickets
 * since the data is 100% ticket-based.
 */
class HelpdeskReportsController extends Controller
{
    /**
     * Show the reports dashboard.
     */
    public function index(Request $request): View
    {
        $this->authorize('helpdesk.metrics.view');

        [$from, $to] = $this->resolveDateRange($request);

        $cacheKey = ReportsCache::key('index:'.$from->format('Y-m-d').':'.$to->format('Y-m-d'));

        // Queries compartidas con el informe programado por email
        // (helpdesk:send-scheduled-reports) — ver TicketReportsService.
        $viewData = Cache::remember(
            $cacheKey,
            300,
            fn () => app(TicketReportsService::class)->summary($from, $to)
        );

        return view('helpdesk::helpdesk.reports.index', array_merge($viewData, [
            'from' => $from,
            'to' => $to,
            // Salud operativa "ahora" (colas, dead-letters, webhooks, SLA, IA):
            // fuera del cache por rango de fechas — la refresca el comando
            // programado helpdesk:ops-metrics y aquí solo se lee.
            'opsHealth' => app(OpsHealthService::class)->cached(),
        ]));
    }

    /**
     * Export ticket stats as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('helpdesk.metrics.export');

        [$from, $to] = $this->resolveDateRange($request);

        $filename = 'tickets-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

        // Filas compartidas con el adjunto del informe programado
        // (TicketsExporter) + streamer del core Helpdesk (BOM UTF-8 y escape
        // anti-inyección de fórmulas incluidos).
        $exporter = new TicketsExporter($from, $to);

        return app(CsvStreamExporter::class)->stream($filename, $exporter->headers(), $exporter->rows());
    }

    /**
     * Resolve from/to dates from request, defaulting to last 30 days.
     * Malformed or inverted input falls back to the default range instead of
     * bubbling a Carbon parse exception (500).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(Request $request): array
    {
        $from = $this->parseDateInput($request->input('from')) ?? now()->subDays(30);
        $to = $this->parseDateInput($request->input('to')) ?? now();

        if ($from->greaterThan($to)) {
            $from = now()->subDays(30);
            $to = now();
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * Parse a request date value, returning null when missing or invalid.
     */
    private function parseDateInput(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
