<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Helpdesk\Filters\TicketFilter;
use Modules\Helpdesk\Services\Exports\CsvStreamExporter;
use Modules\HelpdeskTickets\Models\Ticket;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExportController extends Controller
{
    public function export(Request $request, string $format): StreamedResponse|Response
    {
        $this->authorize('manage', Ticket::class);

        $query = Ticket::query()
            ->with(['customer', 'status', 'category', 'assignee'])
            ->latest();

        // Antes solo aplicaba "search" — el botón "Exportar" del listado
        // pasaba el querystring completo (origen/categoría/agente/
        // prioridad) creyendo que se respetaba, y en realidad se exportaban
        // los 1000 tickets más recientes sin filtrar. Mismo TicketFilter que
        // usa TicketsCrudController::index(), para que "exportar" exporte
        // de verdad lo que se está viendo.
        (new TicketFilter($request))->apply($query);

        $tickets = $query->take(1000)->cursor();

        if ($format === 'csv') {
            return $this->exportCsv($tickets);
        }

        if ($format === 'pdf') {
            return $this->exportPdf($tickets);
        }

        abort(400, 'Formato no soportado.');
    }

    private function exportCsv(iterable $tickets): StreamedResponse
    {
        $filename = 'tickets-'.now()->format('Y-m-d').'.csv';

        $headers = ['Numero', 'Titulo', 'Estado', 'Prioridad', 'Cliente', 'Agente', 'Creado', 'Resuelto'];

        // Título/cliente/agente vienen de fuentes no confiables (formulario
        // público, email entrante), por eso pasa por CsvStreamExporter en vez
        // de fputcsv() directo: neutraliza CSV/formula injection igual que el
        // export de conversaciones del core Helpdesk.
        $rows = (function () use ($tickets) {
            foreach ($tickets as $t) {
                yield [
                    $t->ticket_number,
                    $t->title,
                    $t->status?->name ?? '',
                    $t->priority,
                    $t->customer?->name ?? '',
                    $t->assignee?->name ?? '',
                    $t->created_at?->toDateTimeString(),
                    $t->resolved_at?->toDateTimeString() ?? '',
                ];
            }
        })();

        return app(CsvStreamExporter::class)->stream($filename, $headers, $rows);
    }

    private function exportPdf(iterable $tickets): Response
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('helpdesktickets::managers.tickets.export-pdf', compact('tickets'));

        return $pdf->download('tickets-'.now()->format('Y-m-d').'.pdf');
    }
}
