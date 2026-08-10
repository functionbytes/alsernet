<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Helpdesk\Filters\TicketFilter;
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

        return response()->streamDownload(function () use ($tickets) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Numero', 'Titulo', 'Estado', 'Prioridad', 'Cliente', 'Agente', 'Creado', 'Resuelto']);

            foreach ($tickets as $t) {
                fputcsv($out, [
                    $t->ticket_number,
                    $t->title,
                    $t->status?->name ?? '',
                    $t->priority,
                    $t->customer?->name ?? '',
                    $t->assignee?->name ?? '',
                    $t->created_at?->toDateTimeString(),
                    $t->resolved_at?->toDateTimeString() ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function exportPdf(iterable $tickets): Response
    {
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('helpdesktickets::managers.tickets.export-pdf', compact('tickets'));

        return $pdf->download('tickets-'.now()->format('Y-m-d').'.pdf');
    }
}
