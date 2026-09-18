<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\HelpdeskTickets\Http\Requests\Settings\BulkActionTicketEmailBlacklistRequest;
use Modules\HelpdeskTickets\Http\Requests\Settings\StoreTicketEmailBlacklistRequest;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklistHit;

class TicketEmailBlacklistController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:helpdesk.tickets.settings');
    }

    /**
     * Display the blacklist (single listing, entries are added via a modal).
     */
    public function index(Request $request)
    {
        $query = TicketEmailBlacklist::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('value', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $entries = $query->with('addedBy:id,firstname,lastname')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => TicketEmailBlacklist::count(),
            'active' => TicketEmailBlacklist::where('is_active', true)->count(),
            'emails_blocked' => TicketEmailBlacklist::where('matched_count', '>', 0)->sum('matched_count'),
        ];

        return view('theme.views.backups.helpdesk.ticket-blacklist.index', [
            'entries' => $entries,
            'stats' => $stats,
        ]);
    }

    /**
     * Display the history of individual blocked emails (one row per hit),
     * optionally filtered by rule and/or date range.
     */
    public function history(Request $request)
    {
        $query = TicketEmailBlacklistHit::query()->with('blacklist:id,type,value');

        if ($request->filled('blacklist_id')) {
            $query->where('blacklist_id', $request->integer('blacklist_id'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('from_email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        $hits = $query->latest()->paginate(30)->withQueryString();

        $rules = TicketEmailBlacklist::orderBy('value')->get(['id', 'type', 'value']);

        return view('theme.views.backups.helpdesk.ticket-blacklist.history', [
            'hits' => $hits,
            'rules' => $rules,
        ]);
    }

    /**
     * Preview the exact content of a blocked email (same "vista previa"
     * pattern as Document::emailPreview()).
     */
    public function preview(TicketEmailBlacklistHit $hit): View
    {
        $hit->load('blacklist');

        return view('theme.views.backups.helpdesk.ticket-blacklist.preview', [
            'hit' => $hit,
        ]);
    }

    /**
     * Store a newly created blacklist entry.
     */
    public function store(StoreTicketEmailBlacklistRequest $request)
    {
        $validated = $request->validated();
        $validated['added_by'] = $request->user()->id;

        TicketEmailBlacklist::create($validated);

        // El botón "Bloquear remitente" del detalle de ticket reutiliza este
        // mismo endpoint pero necesita quedarse en el ticket en vez de saltar
        // al índice de Settings — redirect_ticket_id (no pasa por validated(),
        // no se guarda en el modelo) lo indica.
        if ($request->filled('redirect_ticket_id')) {
            return redirect()->route('manager.helpdesk.tickets.show', $request->integer('redirect_ticket_id'))
                ->with('success', __('helpdesktickets::helpdesktickets.settings.blacklist.sender_added'));
        }

        return redirect()->route('manager.helpdesk.settings.ticket-blacklist.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.blacklist.rule_added'));
    }

    /**
     * Toggle the active status of an entry.
     */
    public function toggle(TicketEmailBlacklist $entry)
    {
        $entry->update(['is_active' => ! $entry->is_active]);

        return back()->with('success', __('helpdesktickets::helpdesktickets.settings.blacklist.toggled'));
    }

    /**
     * Remove the specified blacklist entry.
     */
    public function destroy(TicketEmailBlacklist $entry)
    {
        $entry->delete();

        return redirect()->route('manager.helpdesk.settings.ticket-blacklist.index')
            ->with('success', __('helpdesktickets::helpdesktickets.settings.blacklist.deleted'));
    }

    /**
     * Apply a bulk action (activate/deactivate/delete) to several entries at once.
     */
    public function bulkAction(BulkActionTicketEmailBlacklistRequest $request): JsonResponse
    {
        $action = $request->validated('action');
        $ids = $request->validated('ids');
        $count = 0;

        $entries = TicketEmailBlacklist::whereIn('id', $ids)->get();

        if ($action === 'delete') {
            foreach ($entries as $entry) {
                $entry->delete();
                $count++;
            }
        } else {
            $value = $action === 'activate';
            foreach ($entries as $entry) {
                $entry->update(['is_active' => $value]);
                $count++;
            }
        }

        $labels = ['delete' => 'eliminada(s)', 'activate' => 'activada(s)', 'deactivate' => 'desactivada(s)'];
        $message = "{$count} regla(s) {$labels[$action]}.";

        return response()->json(['message' => $message, 'count' => $count]);
    }
}
