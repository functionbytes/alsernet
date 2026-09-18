<?php

namespace Modules\HelpdeskTickets\Services\Exports;

use Carbon\Carbon;
use Generator;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Filas CSV de tickets por rango de fechas, extraídas de
 * HelpdeskReportsController::export para compartirlas entre el export del
 * dashboard (streaming vía CsvStreamExporter del core Helpdesk) y el adjunto
 * del informe programado (helpdesk:send-scheduled-reports, con límite de
 * filas). Mismo patrón headers()/rows() que los exporters del core
 * (ConversationsExporter, CsatExporter...).
 */
class TicketsExporter
{
    public function __construct(
        private readonly Carbon $from,
        private readonly Carbon $to,
        private readonly ?int $maxRows = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function headers(): array
    {
        return [
            'Ticket#', 'Asunto', 'Cliente', 'Email', 'Estado', 'Categoria',
            'Prioridad', 'Agente', 'Creado', 'Cerrado',
            'Tiempo respuesta (min)', 'Tiempo resolucion (min)', 'SLA Incumplido',
        ];
    }

    public function rows(): Generator
    {
        $emitted = 0;

        $tickets = Ticket::with(['customer:id,name,email', 'status:id,name', 'category:id,name', 'assignee:id,firstname,lastname'])
            ->whereBetween('created_at', [$this->from, $this->to])
            ->cursor();

        foreach ($tickets as $ticket) {
            if ($this->maxRows !== null && $emitted >= $this->maxRows) {
                return;
            }

            $responseTime = $ticket->first_response_at
                ? $ticket->created_at->diffInMinutes($ticket->first_response_at)
                : '';

            $resolutionTime = $ticket->closed_at
                ? $ticket->created_at->diffInMinutes($ticket->closed_at)
                : '';

            yield [
                $ticket->ticket_number,
                $ticket->subject,
                $ticket->customer->name ?? '',
                $ticket->customer->email ?? '',
                $ticket->status->name ?? '',
                $ticket->category->name ?? '',
                $ticket->priority,
                $ticket->assignee->name ?? '',
                $ticket->created_at->format('Y-m-d H:i'),
                $ticket->closed_at?->format('Y-m-d H:i') ?? '',
                $responseTime,
                $resolutionTime,
                $ticket->sla_resolution_breached ? 'Si' : 'No',
            ];

            $emitted++;
        }
    }
}
