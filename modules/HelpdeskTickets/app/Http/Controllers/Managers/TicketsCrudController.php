<?php

namespace Modules\HelpdeskTickets\Http\Controllers\Managers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Filters\TicketFilter;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Http\Requests\StoreTicketRequest;
use Modules\HelpdeskTickets\Http\Requests\UpdateTicketRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCannedReply;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Models\TicketRead;
use Modules\HelpdeskTickets\Models\TicketSlaPolicy;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Services\TicketUpdateService;

class TicketsCrudController extends Controller
{
    public function __construct(
        private readonly TicketUpdateService $ticketUpdateService,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $userId = auth()->id();

        $views = TicketView::forUser($userId)->ordered()->limit(100)->get();

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
        $statuses = CatalogCacheService::statuses();
        $categories = CatalogCacheService::categories();
        $groups = CatalogCacheService::groups();
        $agents = User::select(['id', 'firstname', 'lastname'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('firstname')
            ->get();

        return view('helpdesktickets::managers.tickets.index', [
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

    public function create(Request $request)
    {
        $this->authorize('create', Ticket::class);

        $customer = null;
        if ($request->has('customer')) {
            $customer = Customer::findOrFail($request->customer);
        }

        $customers = Customer::orderBy('name')->limit(500)->get();
        $categories = CatalogCacheService::categories();
        $statuses = CatalogCacheService::statuses();
        $defaultStatus = $statuses->firstWhere('is_default', true) ?? $statuses->first();
        $slaPolicies = TicketSlaPolicy::active()->get();
        $groups = CatalogCacheService::groups();
        $agents = User::select(['id', 'firstname', 'lastname'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('firstname')
            ->get();

        return view('helpdesktickets::managers.tickets.create', [
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

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request, &$ticket) {
            $data = $request->validated();

            if (! isset($data['sla_policy_id']) && isset($data['category_id'])) {
                $category = TicketCategory::find($data['category_id']);
                if ($category && $category->default_sla_policy_id) {
                    $data['sla_policy_id'] = $category->default_sla_policy_id;
                }
            }

            $ticket = Ticket::create($data);

            if ($request->hasFile('attachments')) {
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $attachmentPaths[] = $file->store(
                        'helpdesk/tickets/'.$ticket->id,
                        config('helpdesk.attachments.disk', 'local')
                    );
                }

                if (! empty($data['description'])) {
                    $ticket->items()->create([
                        'type' => 'message',
                        'user_id' => auth()->id(),
                        'body' => $data['description'],
                        'attachment_urls' => $attachmentPaths,
                        'is_internal' => false,
                    ]);
                }
            } elseif (! empty($data['description'])) {
                $ticket->items()->create([
                    'type' => 'message',
                    'user_id' => auth()->id(),
                    'body' => $data['description'],
                    'is_internal' => false,
                ]);
            }

            broadcast(new TicketCreated($ticket));
        });

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_created', ['number' => $ticket->ticket_number]));
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $ticket->load(['customer', 'status', 'category', 'assignee', 'group', 'slaPolicy', 'items.user', 'items.author', 'watchers']);

        $sidebarQuery = Ticket::query()
            ->with(['customer', 'status', 'category'])
            ->latest();

        if ($request->has('status') && $request->status !== 'all') {
            $sidebarQuery->where('status_id', $request->status);
        }

        $tickets = $sidebarQuery->paginate(20);

        $statuses = CatalogCacheService::statuses();
        $categories = CatalogCacheService::categories();
        $groups = CatalogCacheService::groups();

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

        $mentionableUsers = User::select(['id', 'firstname', 'lastname', 'email'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('firstname')
            ->take(50)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => trim($u->firstname.' '.$u->lastname),
                'email' => $u->email,
            ])->values();

        $history = $ticket->history()->latest()->limit(50)->get();
        $ticketMails = $ticket->mails()->latest()->limit(30)->get();
        $cannedReplies = TicketCannedReply::where('is_active', true)
            ->where(fn ($q) => $q->where('is_global', true)->orWhere('user_id', $userId))
            ->orderBy('title')
            ->get(['id', 'title', 'content', 'html_body', 'short_code']);

        return view('helpdesktickets::managers.tickets.show', [
            'ticket' => $ticket,
            'tickets' => $tickets,
            'statuses' => $statuses,
            'categories' => $categories,
            'groups' => $groups,
            'mentionableUsers' => $mentionableUsers,
            'history' => $history,
            'ticketMails' => $ticketMails,
            'cannedReplies' => $cannedReplies,
        ]);
    }

    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $categories = CatalogCacheService::categories();
        $statuses = CatalogCacheService::statuses();
        $slaPolicies = TicketSlaPolicy::active()->get();
        $groups = CatalogCacheService::groups();
        $agents = User::select(['id', 'firstname', 'lastname'])
            ->where('available', true)
            ->where('verified', true)
            ->orderBy('firstname')
            ->get();

        return view('helpdesktickets::managers.tickets.edit', [
            'ticket' => $ticket,
            'categories' => $categories,
            'statuses' => $statuses,
            'slaPolicies' => $slaPolicies,
            'groups' => $groups,
            'agents' => $agents,
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->ticketUpdateService->applyChanges($ticket, $request->getModifiableFields(), auth()->user());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('helpdesktickets::helpdesktickets.messages.ticket_updated'),
                'ticket' => $ticket->fresh(['customer', 'status', 'assignee']),
            ]);
        }

        return redirect()
            ->route('manager.helpdesk.tickets.show', $ticket)
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_updated'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return redirect()
            ->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_deleted'));
    }

    public function restore(int $id): RedirectResponse
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $this->authorize('restore', $ticket);
        $ticket->restore();

        return redirect()->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_restored'));
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $this->authorize('forceDelete', $ticket);
        $ticket->forceDelete();

        return redirect()->route('manager.helpdesk.tickets.index')
            ->with('success', __('helpdesktickets::helpdesktickets.messages.ticket_permanently_deleted'));
    }
}
