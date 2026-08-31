<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\TicketSemanticSearchService;

class TicketSearchController extends Controller
{
    public function index(Request $request, TicketSemanticSearchService $semantic): View
    {
        $this->authorize('helpdesk.tickets.view');

        $query = Ticket::query()->with(['customer', 'category', 'status', 'assignee']);

        $semanticIds = [];

        if ($request->filled('q')) {
            $q = $request->q;

            // La búsqueda por significado AMPLÍA la literal, no la sustituye:
            // quien busca un número de ticket o un apellido quiere una
            // coincidencia exacta, y ahí un vector solo añade ruido. El
            // servicio devuelve una lista vacía cuando la consulta pide
            // literal, así que en ese caso esto se comporta como siempre.
            $semanticIds = $semantic->search($q);

            $query->where(fn ($b) => $b
                ->where('title', 'like', "%{$q}%")
                ->orWhere('subject', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->orWhere('ticket_number', 'like', "%{$q}%")
                ->when($semanticIds !== [], fn ($sub) => $sub->orWhereIn('id', $semanticIds))
            );
        }

        foreach (['status_id', 'category_id', 'priority', 'assignee_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to.' 23:59:59');
        }

        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $results = $query->latest()->paginate(25)->appends($request->query());

        $agents = CatalogCacheService::agents();

        return view('helpdesktickets::managers.search.index', [
            'results' => $results,
            'statuses' => TicketStatus::active()->ordered()->get(),
            'categories' => TicketCategory::active()->ordered()->get(),
            'agents' => $agents,
            'filters' => $request->only(['q', 'status_id', 'category_id', 'priority', 'assignee_id', 'from', 'to', 'tag']),
            // Para poder marcar en el listado qué resultados no habrían
            // aparecido con la búsqueda literal.
            'semanticIds' => $semanticIds,
        ]);
    }
}
