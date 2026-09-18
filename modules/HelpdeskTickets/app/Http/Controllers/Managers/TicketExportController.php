<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Helpdesk\Filters\TicketFilter;
use Modules\Helpdesk\Services\Exports\CsvStreamExporter;
use Modules\HelpdeskTickets\Models\Ticket;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketExportController extends Controller
{
    /**
     * Columnas que se pueden exportar.
     *
     * La clave viaja en la petición y el valor es la cabecera del CSV. El
     * catálogo vive aquí y no en el JS para que no se puedan pedir columnas
     * que nadie sabe rellenar.
     *
     * @var array<string, string>
     */
    private const COLUMNS = [
        'ticket_number' => 'Numero',
        'subject' => 'Asunto',
        'status' => 'Estado',
        'priority' => 'Prioridad',
        'category' => 'Categoria',
        'customer' => 'Cliente',
        'customer_email' => 'Email del cliente',
        'assignee' => 'Agente',
        'group' => 'Equipo',
        'source' => 'Origen',
        'tags' => 'Etiquetas',
        'created_at' => 'Creado',
        'first_response_at' => 'Primera respuesta',
        'resolved_at' => 'Resuelto',
        'closed_at' => 'Cerrado',
        'close_reason' => 'Motivo de cierre',
        'close_root_cause' => 'Causa raiz',
        'sla_resolution_due_at' => 'Vencimiento SLA',
        'rating' => 'Valoracion',
    ];

    /**
     * Columnas por defecto: las que traía el CSV original.
     *
     * @var list<string>
     */
    private const DEFAULT_COLUMNS = [
        'ticket_number', 'subject', 'status', 'priority', 'customer', 'assignee', 'created_at', 'resolved_at',
    ];

    public function export(Request $request, string $format): StreamedResponse|Response
    {
        $this->authorize('manage', Ticket::class);

        $columns = $this->resolveColumns($request);

        if ($format === 'csv') {
            return $this->exportCsv($this->query($request)->lazyById(500), $columns);
        }

        if ($format === 'pdf') {
            return $this->exportPdf($this->query($request)->lazyById(500));
        }

        abort(400, 'Formato no soportado.');
    }

    /**
     * Cuántas filas saldrían con el alcance elegido, para que el modal diga
     * qué se va a descargar ANTES de descargarlo (una exportación de 20 000
     * filas y otra de 12 no se piden igual).
     */
    public function estimate(Request $request): JsonResponse
    {
        $this->authorize('manage', Ticket::class);

        $total = $this->query($request)->count();

        return response()->json([
            'total' => $total,
            'columns' => collect(self::COLUMNS)->map(fn ($label, $key) => [
                'key' => $key,
                'label' => $label,
                'default' => in_array($key, self::DEFAULT_COLUMNS, true),
            ])->values()->all(),
        ]);
    }

    /**
     * Consulta base según el alcance pedido.
     *
     * - `selection`: solo los ids marcados en el listado.
     * - `all`: todo el histórico, ignorando los filtros de pantalla.
     * - por defecto: lo que se está viendo (mismo TicketFilter que el index).
     */
    private function query(Request $request)
    {
        $query = Ticket::query()->with(['customer', 'status', 'category', 'assignee', 'group']);

        $scope = $request->input('scope', 'filter');

        if ($scope === 'selection') {
            $ids = collect(explode(',', (string) $request->input('ids')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->take(1000)
                ->all();

            // Sin ids no se exporta el listado entero por descuido: una
            // selección vacía exporta cero filas, que es lo que se pidió.
            return $query->whereIn('id', $ids ?: [0]);
        }

        if ($scope === 'all') {
            return $query;
        }

        // Antes solo aplicaba "search" — el botón "Exportar" del listado
        // pasaba el querystring completo (origen/categoría/agente/
        // prioridad) creyendo que se respetaba, y en realidad se exportaban
        // los 1000 tickets más recientes sin filtrar. Mismo TicketFilter que
        // usa TicketsCrudController::index(), para que "exportar" exporte
        // de verdad lo que se está viendo.
        (new TicketFilter($request))->apply($query);

        return $query;
    }

    /**
     * @return list<string>
     */
    private function resolveColumns(Request $request): array
    {
        $pedidas = collect(explode(',', (string) $request->input('columns')))
            ->map(fn ($c) => trim($c))
            ->filter(fn ($c) => array_key_exists($c, self::COLUMNS))
            ->values()
            ->all();

        return $pedidas ?: self::DEFAULT_COLUMNS;
    }

    /**
     * Valor de una columna para un ticket.
     */
    private function value(Ticket $ticket, string $column): string
    {
        return match ($column) {
            'ticket_number' => (string) $ticket->ticket_number,
            // `title` NO existe como columna: la tabla guarda el asunto en
            // `subject`, así que esta columna del CSV salía vacía en todas
            // las filas de todas las exportaciones.
            'subject' => (string) $ticket->subject,
            'status' => (string) ($ticket->status?->name ?? ''),
            'priority' => (string) $ticket->priority,
            'category' => (string) ($ticket->category?->name ?? ''),
            'customer' => (string) ($ticket->customer?->name ?? ''),
            'customer_email' => (string) ($ticket->customer?->email ?? ''),
            // Mismo motivo: el User de esta app guarda firstname/lastname y
            // no tiene columna `name`, así que la columna Agente también
            // salía vacía siempre.
            'assignee' => (string) ($ticket->assignee?->fullName() ?? ''),
            'group' => (string) ($ticket->group?->name ?? ''),
            'source' => (string) $ticket->sourceSlug(),
            'tags' => is_array($ticket->tags) ? implode(' | ', $ticket->tags) : '',
            'created_at' => (string) $ticket->created_at?->toDateTimeString(),
            'first_response_at' => (string) $ticket->first_response_at?->toDateTimeString(),
            'resolved_at' => (string) $ticket->resolved_at?->toDateTimeString(),
            'closed_at' => (string) $ticket->closed_at?->toDateTimeString(),
            'close_reason' => (string) $ticket->close_reason,
            'close_root_cause' => (string) (config('helpdesktickets.close_root_causes')[$ticket->close_root_cause] ?? $ticket->close_root_cause),
            'sla_resolution_due_at' => (string) $ticket->sla_resolution_due_at?->toDateTimeString(),
            'rating' => $ticket->rating !== null ? (string) $ticket->rating : '',
            default => '',
        };
    }

    /**
     * @param  list<string>  $columns
     */
    private function exportCsv(iterable $tickets, array $columns): StreamedResponse
    {
        $filename = 'tickets-'.now()->format('Y-m-d').'.csv';

        $headers = array_map(fn ($c) => self::COLUMNS[$c], $columns);

        // Asunto/cliente/agente vienen de fuentes no confiables (formulario
        // público, email entrante), por eso pasa por CsvStreamExporter en vez
        // de fputcsv() directo: neutraliza CSV/formula injection igual que el
        // export de conversaciones del core Helpdesk.
        $rows = (function () use ($tickets, $columns) {
            foreach ($tickets as $ticket) {
                yield array_map(fn ($column) => $this->value($ticket, $column), $columns);
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
