<?php

namespace Modules\Helpdesk\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Events\NewTicketMessage;
use Modules\Helpdesk\Events\TicketCreated;
use Modules\Helpdesk\Events\TicketMessageReceived;
use Modules\Helpdesk\Filters\TicketFilter;
use Modules\Helpdesk\Http\Requests\StoreTicketRequest;
use Modules\Helpdesk\Http\Requests\UpdateTicketRequest;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Group;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketCategory;
use Modules\Helpdesk\Models\TicketRead;
use Modules\Helpdesk\Models\TicketSlaPolicy;
use Modules\Helpdesk\Models\TicketStatus;
use Modules\Helpdesk\Models\TicketView;
use Modules\Helpdesk\Models\TicketWatcher;
use Modules\Helpdesk\Services\SlaService;
use Modules\Helpdesk\Services\TicketUpdateService;

class TicketsController extends Controller
{
    public function __construct(private TicketUpdateService $ticketUpdateService) {}

    /**
     * Display a listing of tickets
     */
    public function index(Request $request)
    {
        $this->authorize('manager.helpdesk.tickets.index');

        $userId = auth()->id();

        $views = TicketView::forUser($userId)->ordered()->get();

        $currentView = null;
        if ($request->has('viewId')) {
            $currentView = $views->firstWhere('id', $request->viewId);
        }

        if (! $currentView) {
            $currentView = $views->firstWhere('is_default', true) ?? $views->first();
        }

        $filter = new TicketFilter($request);

        $query = Ticket::query()
            ->with(['customer', 'status', 'category', 'assignee'])
            ->latest();

        if ($currentView && ! empty($currentView->filters)) {
            $filter->applyViewFilters($query, $currentView->filters);
        }

        $filter->apply($query);

        $tickets = $query->paginate(50)->appends($request->query());
        $statuses = TicketStatus::active()->ordered()->get();
        $categories = TicketCategory::active()->ordered()->get();
        $groups = Group::orderBy('name')->get();
        $agents = User::select(['id', 'name'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('name')
            ->get();

        return view('helpdesk::managers.helpdesk.tickets.index', [
            'tickets' => $tickets,
            'statuses' => $statuses,
            'categories' => $categories,
            'groups' => $groups,
            'agents' => $agents,
            'views' => $views,
            'currentView' => $currentView,
            'filters' => $request->only(['status', 'category', 'assignee', 'group', 'priority', 'source', 'sla_status', 'search', 'archived']),
        ]);
    }

    /**
     * Show the form for creating a new ticket
     */
    public function create(Request $request)
    {
        $this->authorize('manager.helpdesk.tickets.create');

        $customer = null;
        if ($request->has('customer')) {
            $customer = Customer::findOrFail($request->customer);
        }

        $customers = Customer::orderBy('name')->get();
        $categories = TicketCategory::active()->ordered()->get();
        $statuses = TicketStatus::active()->ordered()->get();
        $defaultStatus = TicketStatus::where('is_default', true)->first() ?? $statuses->first();
        $slaPolicies = TicketSlaPolicy::active()->get();
        $groups = Group::orderBy('name')->get();

        // Get available agents (users with helpdesk permissions)
        $agents = User::select(['id', 'name'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('name')
            ->get();

        return view('helpdesk::managers.helpdesk.tickets.create', [
            'customer' => $customer,
            'customers' => $customers,
            'categories' => $categories,
            'statuses' => $statuses,
            'defaultStatus' => $defaultStatus,
            'slaPolicies' => $slaPolicies,
            'groups' => $groups,
            'agents' => $agents,
        ]);
    }

    /**
     * Store a newly created ticket
     */
    public function store(StoreTicketRequest $request)
    {
        DB::transaction(function () use ($request, &$ticket) {
            // Create the ticket
            $data = $request->validated();

            // Set SLA policy from category if not provided
            if (! isset($data['sla_policy_id']) && isset($data['category_id'])) {
                $category = TicketCategory::find($data['category_id']);
                if ($category && $category->default_sla_policy_id) {
                    $data['sla_policy_id'] = $category->default_sla_policy_id;
                }
            }

            $ticket = Ticket::create($data);

            // Handle attachments if present
            if ($request->hasFile('attachments')) {
                $attachmentUrls = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('helpdesk/tickets/'.$ticket->id, 'public');
                    $attachmentUrls[] = Storage::url($path);
                }

                // Create first item with attachments if description exists
                if (! empty($data['description'])) {
                    $ticket->items()->create([
                        'type' => 'message',
                        'user_id' => auth()->id(),
                        'body' => $data['description'],
                        'attachment_urls' => $attachmentUrls,
                        'is_internal' => false,
                    ]);
                }
            } elseif (! empty($data['description'])) {
                // Create first item with description
                $ticket->items()->create([
                    'type' => 'message',
                    'user_id' => auth()->id(),
                    'body' => $data['description'],
                    'is_internal' => false,
                ]);
            }

            // Broadcast event
            broadcast(new TicketCreated($ticket));
        });

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_created', ['number' => $ticket->ticket_number]));
    }

    /**
     * Display the specified ticket
     */
    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.show');

        // Load relationships
        $ticket->load(['customer', 'status', 'category', 'assignee', 'group', 'slaPolicy', 'items.user', 'items.author', 'watchers']);

        // Get sidebar ticket list (same filters as index)
        $sidebarQuery = Ticket::query()
            ->with(['customer', 'status', 'category'])
            ->latest();

        // Apply same filters as index for consistency
        if ($request->has('status') && $request->status !== 'all') {
            $sidebarQuery->where('status_id', $request->status);
        }

        $tickets = $sidebarQuery->paginate(20);

        // Get available statuses and categories for quick actions
        $statuses = TicketStatus::active()->ordered()->get();
        $categories = TicketCategory::active()->ordered()->get();
        $groups = Group::orderBy('name')->get();

        // Mark items as read for current user (bulk insert to avoid N+1)
        $userId = auth()->id();
        $itemIds = $ticket->items->pluck('id');

        if ($itemIds->isNotEmpty()) {
            $alreadyRead = TicketRead::where('user_id', $userId)
                ->whereIn('ticket_item_id', $itemIds)
                ->pluck('ticket_item_id')
                ->all();

            $toInsert = $itemIds
                ->diff($alreadyRead)
                ->map(fn ($id) => [
                    'ticket_item_id' => $id,
                    'user_id' => $userId,
                    'read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            if (! empty($toInsert)) {
                TicketRead::insert($toInsert);
            }
        }

        return view('helpdesk::managers.helpdesk.tickets.show', [
            'ticket' => $ticket,
            'tickets' => $tickets,
            'statuses' => $statuses,
            'categories' => $categories,
            'groups' => $groups,
        ]);
    }

    /**
     * Show the form for editing the ticket
     */
    public function edit(Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.update');

        $categories = TicketCategory::active()->ordered()->get();
        $statuses = TicketStatus::active()->ordered()->get();
        $slaPolices = TicketSlaPolicy::active()->get();
        $groups = Group::orderBy('name')->get();
        $agents = User::select(['id', 'name'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('name')
            ->get();

        return view('helpdesk::managers.helpdesk.tickets.edit', [
            'ticket' => $ticket,
            'categories' => $categories,
            'statuses' => $statuses,
            'slaPolicies' => $slaPolices,
            'groups' => $groups,
            'agents' => $agents,
        ]);
    }

    /**
     * Update the specified ticket
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->ticketUpdateService->applyChanges($ticket, $request->getModifiableFields(), auth()->user());

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_updated'));
    }

    /**
     * Remove the specified ticket (soft delete)
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.delete');

        $ticket->delete();

        return redirect()
            ->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.ticket_deleted'));
    }

    /**
     * Close the ticket
     */
    public function close(Request $request, Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.close');

        $ticket->close();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.ticket_closed'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_closed'));
    }

    /**
     * Mark ticket as resolved
     */
    public function resolve(Request $request, Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.resolve');

        $ticket->resolve();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.ticket_resolved'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_resolved'));
    }

    /**
     * Reopen a closed ticket
     */
    public function reopen(Request $request, Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.reopen');

        $ticket->reopen();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.ticket_reopened'),
                'ticket' => $ticket->fresh(),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_reopened'));
    }

    /**
     * Archive the ticket
     */
    public function archive(Request $request, Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.archive');

        $ticket->archive();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.ticket_archived'),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.ticket_archived'));
    }

    /**
     * Unarchive the ticket
     */
    public function unarchive(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        $ticket->update(['archived_at' => null]);

        return back()->with('success', __('helpdesk::helpdesk.messages.ticket_unarchived'));
    }

    /**
     * Restore a soft-deleted ticket
     *
     * POST /manager/helpdesk/tickets/{ticket}/restore
     */
    public function restore(int $id): RedirectResponse
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $this->authorize('restore', $ticket);
        $ticket->restore();

        return redirect()->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.ticket_restored'));
    }

    /**
     * Permanently delete a ticket
     *
     * DELETE /manager/helpdesk/tickets/{ticket}/force-delete
     */
    public function forceDelete(int $id): RedirectResponse
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $ticket);
        $ticket->forceDelete();

        return redirect()->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesk::helpdesk.messages.ticket_permanently_deleted'));
    }

    /**
     * Merge a ticket into another ticket
     *
     * POST /manager/helpdesk/tickets/{ticket}/merge
     */
    public function merge(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('manager.helpdesk.tickets.update');

        $validated = $request->validate([
            'merge_into_id' => ['required', 'integer', 'exists:helpdesk_tickets,id', 'different:'.$ticket->id],
        ]);

        $targetTicket = Ticket::findOrFail($validated['merge_into_id']);

        DB::transaction(function () use ($ticket, $targetTicket) {
            // Move all items (messages/events) to target
            $ticket->items()->update(['ticket_id' => $targetTicket->id]);

            // Move watchers (skip duplicates via firstOrCreate in TicketWatcher)
            $ticket->watchers()->each(function (TicketWatcher $watcher) use ($targetTicket) {
                TicketWatcher::firstOrCreate([
                    'ticket_id' => $targetTicket->id,
                    'user_id' => $watcher->user_id,
                ]);
            });

            // Add history item on target
            $targetTicket->items()->create([
                'type' => 'system',
                'body' => "Merged from #{$ticket->ticket_number}",
                'metadata' => ['merged_from_ticket_id' => $ticket->id],
            ]);

            // Close and soft-delete the source ticket
            $ticket->close();
            $ticket->delete();
        });

        return redirect()->route('manager.helpdesk.tickets.show', $targetTicket)
            ->with('success', __('helpdesk::helpdesk.messages.ticket_merged', ['source' => $ticket->ticket_number, 'target' => $targetTicket->ticket_number]));
    }

    /**
     * Subscribe the current user to ticket updates
     *
     * POST /manager/helpdesk/tickets/{ticket}/watch
     */
    public function watch(Ticket $ticket): JsonResponse
    {
        $this->authorize('manager.helpdesk.tickets.show');

        TicketWatcher::addWatcher($ticket->id, auth()->id());

        return response()->json(['watching' => true, 'message' => __('helpdesk::helpdesk.messages.ticket_watched')]);
    }

    /**
     * Unsubscribe the current user from ticket updates
     *
     * DELETE /manager/helpdesk/tickets/{ticket}/watch
     */
    public function unwatch(Ticket $ticket): JsonResponse
    {
        $this->authorize('manager.helpdesk.tickets.show');

        TicketWatcher::removeWatcher($ticket->id, auth()->id());

        return response()->json(['watching' => false, 'message' => __('helpdesk::helpdesk.messages.ticket_unwatched')]);
    }

    /**
     * Pause SLA for a ticket (waiting for customer response).
     */
    public function pauseSla(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        app(SlaService::class)->pauseSla($ticket);

        return back()->with('success', __('helpdesk::helpdesk.messages.sla_paused'));
    }

    /**
     * Resume SLA for a ticket.
     */
    public function resumeSla(Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);
        app(SlaService::class)->resumeSla($ticket);

        return back()->with('success', __('helpdesk::helpdesk.messages.sla_resumed'));
    }

    /**
     * Add a message to the ticket
     */
    public function storeMessage(Request $request, Ticket $ticket)
    {
        $this->authorize('manager.helpdesk.tickets.reply');

        $request->validate([
            'body' => 'required|string|min:1|max:10000',
            'is_internal' => 'sometimes|boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip,rar',
        ]);

        $attachmentUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('helpdesk/tickets/'.$ticket->id, 'public');
                $attachmentUrls[] = Storage::url($path);
            }
        }

        $item = $ticket->items()->create([
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => $request->body,
            'attachment_urls' => $attachmentUrls,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        // Update ticket's last_message_at
        $ticket->update(['last_message_at' => now()]);

        // Set first_response_at if this is the first agent response
        if (! $ticket->first_response_at) {
            $ticket->update(['first_response_at' => now()]);
        }

        // Broadcast events
        broadcast(new TicketMessageReceived($ticket, $item));
        NewTicketMessage::dispatch($ticket, [
            'id' => $item->id,
            'content' => $item->body,
            'author' => auth()->user()->name,
            'created_at' => $item->created_at->toIso8601String(),
            'type' => 'agent',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesk::helpdesk.messages.message_sent'),
                'item' => $item->load(['user']),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesk::helpdesk.messages.message_sent'));
    }
}
