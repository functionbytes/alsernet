<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use League\Csv\EscapeFormula;
use League\Csv\Writer;
use Modules\HelpdeskTickets\Mail\ScheduledReportMail;
use Modules\HelpdeskTickets\Services\Exports\TicketsExporter;
use Modules\HelpdeskTickets\Services\OpsHealthService;
use Modules\HelpdeskTickets\Services\TicketReportsService;

/**
 * Informe periódico del helpdesk por email (OFF por defecto, ver
 * config helpdesktickets.reports.scheduled).
 *
 * Compone el resumen del periodo con las MISMAS queries del dashboard de
 * reports (TicketReportsService, compartido con HelpdeskReportsController) y
 * la salud operativa de OpsHealthService, y lo encola como mail HTML
 * (ScheduledReportMail, cola 'emails') con el CSV de tickets del periodo como
 * adjunto opcional (TicketsExporter, con límite de filas). La cadencia
 * (semanal/mensual) la decide el scheduler del módulo; este comando envía
 * siempre que el toggle esté activo, con el periodo derivado de la frecuencia.
 */
class SendScheduledReportsCommand extends Command
{
    protected $signature = 'helpdesk:send-scheduled-reports';

    protected $description = 'Envía el informe periódico del helpdesk (tickets, CSAT, salud operativa) por email';

    public function handle(TicketReportsService $reports, OpsHealthService $ops): int
    {
        if (! config('helpdesktickets.reports.scheduled.enabled', false)) {
            $this->line('Informes programados desactivados (helpdesktickets.reports.scheduled.enabled).');

            return self::SUCCESS;
        }

        $recipients = $this->recipients();

        if ($recipients === []) {
            Log::warning('Helpdesk scheduled report: no recipients resolved, nothing sent.');
            $this->warn('Sin destinatarios: no se envía el informe.');

            return self::SUCCESS;
        }

        [$from, $to, $periodLabel] = $this->period();

        $summary = $reports->summary($from, $to);
        $sections = (array) config('helpdesktickets.reports.scheduled.sections', []);
        $opsSnapshot = ($sections['ops'] ?? true) ? $ops->cached() : null;

        $subject = sprintf(
            '[Helpdesk] Informe %s: %s — %s',
            $periodLabel,
            $from->format('d/m/Y'),
            $to->format('d/m/Y')
        );

        $html = $this->buildHtml($summary, $opsSnapshot, $from, $to, $periodLabel, $sections);

        [$csv, $csvFilename] = $this->buildCsvAttachment($from, $to);

        foreach ($recipients as $email) {
            Mail::to($email)->queue(new ScheduledReportMail($subject, $html, $csv, $csvFilename));
        }

        Log::info('Helpdesk scheduled report queued.', [
            'recipients' => count($recipients),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'csv_attached' => $csv !== null,
        ]);

        $this->info(sprintf('Informe %s encolado para %d destinatario(s).', $periodLabel, count($recipients)));

        return self::SUCCESS;
    }

    /**
     * Periodo del informe según la frecuencia configurada: ventana móvil de 7
     * días (weekly) o 30 días (monthly), coherente con el rango por defecto
     * del dashboard de reports.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function period(): array
    {
        $frequency = (string) config('helpdesktickets.reports.scheduled.frequency', 'weekly');

        return $frequency === 'monthly'
            ? [now()->subDays(30)->startOfDay(), now(), 'mensual']
            : [now()->subDays(7)->startOfDay(), now(), 'semanal'];
    }

    /**
     * Destinatarios: lista de emails configurada o, en su defecto, los
     * usuarios con permiso de reports (helpdesk.metrics.view — el mismo que
     * protege el dashboard).
     *
     * @return list<string>
     */
    private function recipients(): array
    {
        $configured = config('helpdesktickets.reports.scheduled.recipients', '');

        $emails = array_values(array_filter(array_map(
            'trim',
            is_array($configured) ? $configured : explode(',', (string) $configured)
        ), fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false));

        if ($emails !== []) {
            return array_values(array_unique($emails));
        }

        try {
            return User::permission('helpdesk.metrics.view')
                ->get(['id', 'email'])
                ->pluck('email')
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Helpdesk scheduled report: could not resolve recipients by permission.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * CSV de tickets del periodo (adjunto opcional) usando el mismo exporter
     * que el export del dashboard, con límite de filas.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function buildCsvAttachment(Carbon $from, Carbon $to): array
    {
        if (! config('helpdesktickets.reports.scheduled.attach_csv', true)) {
            return [null, null];
        }

        try {
            $maxRows = max(1, (int) config('helpdesktickets.reports.scheduled.csv_max_rows', 5000));
            $exporter = new TicketsExporter($from, $to, $maxRows);

            $writer = Writer::createFromString();
            $writer->addFormatter(new EscapeFormula);
            $writer->insertOne($exporter->headers());

            foreach ($exporter->rows() as $row) {
                $writer->insertOne($row);
            }

            $filename = 'tickets-'.$from->format('Y-m-d').'-to-'.$to->format('Y-m-d').'.csv';

            // BOM UTF-8 para Excel, igual que CsvStreamExporter.
            return ["\xEF\xBB\xBF".$writer->toString(), $filename];
        } catch (\Throwable $e) {
            Log::warning('Helpdesk scheduled report: CSV attachment failed, sending without it.', [
                'error' => $e->getMessage(),
            ]);

            return [null, null];
        }
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>|null  $opsSnapshot
     * @param  array<string, mixed>  $sections
     */
    private function buildHtml(array $summary, ?array $opsSnapshot, Carbon $from, Carbon $to, string $periodLabel, array $sections): string
    {
        $html = '<h2>Informe '.e($periodLabel).' del helpdesk</h2>'
            .'<p>Periodo: '.e($from->format('d/m/Y H:i')).' — '.e($to->format('d/m/Y H:i')).'</p>';

        if ($sections['tickets'] ?? true) {
            $html .= '<h3>Resumen de tickets</h3>'
                .$this->renderTable([
                    'Tickets creados' => $summary['totalCreated'],
                    'Tickets cerrados' => $summary['totalClosed'],
                    'Tickets resueltos' => $summary['totalResolved'],
                    'SLA incumplidos' => $summary['slaBreached'],
                    'Tiempo medio de respuesta (min)' => $summary['avgResponseTime'],
                    'Tiempo medio de resolucion (min)' => $summary['avgResolutionTime'],
                ]);

            if ($summary['byPriority']->isNotEmpty()) {
                $html .= '<h4>Por prioridad</h4>'
                    .$this->renderTable(
                        $summary['byPriority']
                            ->mapWithKeys(fn ($row) => [(string) ($row->priority ?? 'sin prioridad') => (int) $row->count])
                            ->all()
                    );
            }

            if ($summary['topAgents']->isNotEmpty()) {
                $html .= '<h4>Top agentes (tickets cerrados)</h4>'
                    .$this->renderTable(
                        $summary['topAgents']
                            ->mapWithKeys(fn ($row) => [(string) ($row['agent']->name ?? trim(($row['agent']->firstname ?? '').' '.($row['agent']->lastname ?? ''))) => (int) $row['closed_count']])
                            ->all()
                    );
            }
        }

        if ($sections['csat'] ?? true) {
            $html .= '<h3>CSAT y valoraciones</h3>'
                .$this->renderTable([
                    'CSAT medio' => $summary['csatAvg'],
                    'Respuestas CSAT' => $summary['csatTotal'],
                    'CSAT positivas (>= 4)' => $summary['csatPositive'],
                    'Valoracion media de tickets' => $summary['avgRating'],
                    'Tickets valorados' => $summary['ratedCount'],
                ]);
        }

        if ($opsSnapshot !== null) {
            $html .= '<h3>Salud operativa (ahora)</h3>'
                .$this->renderTable([
                    'Jobs en cola' => $opsSnapshot['queue_total'] ?? 0,
                    'Dead-letters (failed_jobs)' => $this->nullable($opsSnapshot['failed_jobs'] ?? null),
                    'Webhooks activos con error' => $this->nullable($opsSnapshot['webhooks_failing'] ?? null),
                    'Breaches SLA (ultima hora)' => $this->nullable($opsSnapshot['sla_breaches_last_hour'] ?? null),
                    'Sin asignar con SLA proximo' => $opsSnapshot['unassigned_sla_warning'] ?? 0,
                ]);
        }

        return $html.'<p>Generado: '.e(now()->toDateTimeString()).'</p>';
    }

    /**
     * @param  array<string, mixed>  $rows
     */
    private function renderTable(array $rows): string
    {
        $cells = '';

        foreach ($rows as $label => $value) {
            $cells .= '<tr>'
                .'<td style="padding:4px 12px;border:1px solid #ddd;">'.e($label).'</td>'
                .'<td style="padding:4px 12px;border:1px solid #ddd;text-align:right;">'.e((string) $value).'</td>'
                .'</tr>';
        }

        return '<table style="border-collapse:collapse;margin:8px 0;">'.$cells.'</table>';
    }

    private function nullable(?int $value): string
    {
        return $value === null ? 'n/d' : (string) $value;
    }
}
