<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
use Modules\HelpdeskTickets\Support\ReportsCache;

/**
 * Concrete implementation of the Helpdesk → Tickets bridge.
 * Bound to TicketServiceContract by HelpdeskServiceProvider when
 * helpdesk_tickets_enabled() returns true. The Helpdesk module never
 * imports HelpdeskTickets symbols directly — it always goes through this.
 */
class HelpdeskTicketBridgeService implements TicketServiceContract
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function createFromConversation(Conversation $conversation, array $payload = []): ?array
    {
        $firstItem = $conversation->items()
            ->whereNull('user_id')
            ->oldest()
            ->first()
            ?? $conversation->items()->oldest()->first();

        $rawTitle = $firstItem ? strip_tags($firstItem->body ?? '') : '';
        $defaultTitle = blank($rawTitle)
            ? "Conversación #{$conversation->id}"
            : Str::limit($rawTitle, 100, '…');

        $ticket = Ticket::create([
            'customer_id' => $conversation->customer_id,
            'conversation_id' => $conversation->id,
            'subject' => $payload['subject'] ?? $defaultTitle,
            'description' => $payload['description'] ?? ($firstItem?->body ?? ''),
            'priority' => $payload['priority'] ?? 'normal',
            'category_id' => $payload['category_id'] ?? null,
            'source' => $payload['source'] ?? 'conversation',
            'assignee_id' => $payload['assignee_id'] ?? $conversation->assignee_id,
        ]);

        $ticket->items()->create([
            'type' => 'message',
            'user_id' => auth()->id(),
            'body' => "Ticket creado desde conversación #{$conversation->id}.",
            'is_internal' => true,
        ]);

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'url' => route('manager.helpdesk.tickets.show', $ticket),
        ];
    }

    /**
     * Create a ticket directly for a customer (no originating conversation).
     * Used by the Contacts 360 panel. Returns the same compact shape as
     * createFromConversation so callers stay decoupled from Ticket internals.
     *
     * @param  array{subject?: string, message?: string, description?: string, priority?: string, category_id?: int|null, assignee_id?: int|null}  $payload
     * @return array{id: int, ticket_number: string, subject: string, status: ?string, priority: ?string, url: string}|null
     */
    public function createForCustomer(Customer $customer, array $payload = []): ?array
    {
        $subject = trim((string) ($payload['subject'] ?? ''));
        $description = $payload['description'] ?? $payload['message'] ?? '';

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'subject' => $subject !== '' ? Str::limit($subject, 191, '') : 'Nueva consulta',
            'description' => $description,
            'priority' => $payload['priority'] ?? 'normal',
            'category_id' => $payload['category_id'] ?? null,
            'source' => 'contacts',
            'assignee_id' => $payload['assignee_id'] ?? null,
        ]);

        if (! blank($description)) {
            $ticket->items()->create([
                'type' => 'message',
                'user_id' => auth()->id(),
                'body' => $description,
                'is_internal' => false,
            ]);
        }

        $ticket->loadMissing('status');

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'status' => $ticket->status?->name,
            'priority' => $ticket->priority,
            'url' => route('manager.helpdesk.tickets.show', $ticket),
        ];
    }

    public function getCustomerTickets(Customer $customer, int $limit = 5): Collection
    {
        return Ticket::query()
            ->where('customer_id', $customer->id)
            ->with('status')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getConversationTickets(Conversation $conversation): Collection
    {
        return Ticket::query()
            ->where('conversation_id', $conversation->id)
            ->with('status')
            ->latest()
            ->get();
    }

    public function getCategories(): Collection
    {
        return TicketCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);
    }

    public function getTicketDetail(int $ticketId): ?array
    {
        $ticket = Ticket::with(['status', 'category', 'assignee', 'customer', 'items'])
            ->find($ticketId);

        if (! $ticket) {
            return null;
        }

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => [
                'name' => $ticket->status?->name ?? 'Abierto',
                'color' => $ticket->status?->color ?? '#6c757d',
            ],
            'category' => $ticket->category?->name,
            'assignee' => $ticket->assignee?->name ?? 'Sin asignar',
            'group' => $ticket->group?->name ?? '—',
            'customer' => [
                'name' => $ticket->customer?->name ?? '—',
                'email' => $ticket->customer?->email ?? '—',
            ],
            'source' => $ticket->source ?? 'widget',
            'created_at' => $ticket->created_at?->translatedFormat('d M Y H:i'),
            'updated_at' => $ticket->updated_at?->diffForHumans(),
            'items_count' => $ticket->items?->count() ?? 0,
            'url' => route('manager.helpdesk.tickets.show', $ticket),
        ];
    }

    public function getDashboardData(): array
    {
        $stats = Cache::remember(ReportsCache::dashboardKey('ticket_stats'), 300, function () {
            $row = Ticket::query()
                ->selectRaw('
                    SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open,
                    SUM(CASE WHEN DATE(closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as created_today,
                    SUM(CASE WHEN sla_resolution_breached = 1 AND closed_at IS NULL THEN 1 ELSE 0 END) as sla_breached,
                    SUM(CASE WHEN assignee_id IS NULL AND closed_at IS NULL THEN 1 ELSE 0 END) as unassigned,
                    SUM(CASE WHEN sla_resolution_due_at IS NOT NULL AND sla_resolution_due_at < NOW() AND closed_at IS NULL THEN 1 ELSE 0 END) as overdue
                ')
                ->first();

            return [
                'open' => (int) $row->open,
                'closed_today' => (int) $row->closed_today,
                'created_today' => (int) $row->created_today,
                'sla_breached' => (int) $row->sla_breached,
                'unassigned' => (int) $row->unassigned,
                'overdue' => (int) $row->overdue,
            ];
        });

        $topAgents = Cache::remember(ReportsCache::dashboardKey('agent_stats'), 300, function () {
            // User lives on the default connection while helpdesk_tickets may live
            // in a separate helpdesk database, so the joined table is fully
            // qualified to keep the aggregate working when the schemas differ.
            $ticketsTable = (new Ticket)->getConnection()->getDatabaseName().'.helpdesk_tickets';

            return User::select(['users.id', 'users.firstname', 'users.lastname'])
                ->selectRaw('COUNT(t.id) as open_tickets')
                ->selectRaw('SUM(CASE WHEN DATE(t.closed_at) = CURDATE() THEN 1 ELSE 0 END) as closed_today')
                ->join("{$ticketsTable} as t", 't.assignee_id', '=', 'users.id')
                ->whereNull('t.closed_at')
                ->groupBy('users.id', 'users.firstname', 'users.lastname')
                ->orderByDesc('open_tickets')
                ->limit(5)
                ->get();
        });

        $recentBreaches = Cache::remember(ReportsCache::dashboardKey('recent_breaches'), 60, function () {
            return Ticket::query()
                ->where('sla_resolution_breached', true)
                ->whereNull('closed_at')
                ->with(['customer:id,name', 'assignee:id,firstname,lastname', 'status:id,name,color'])
                ->orderBy('sla_resolution_due_at')
                ->limit(5)
                ->get();
        });

        $recentTickets = Cache::remember(ReportsCache::dashboardKey('recent_tickets'), 60, function () {
            return Ticket::query()
                ->with(['customer:id,name', 'status:id,name,color', 'assignee:id,firstname,lastname'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        });

        $avgRating = round(
            Ticket::query()
                ->whereNotNull('rated_at')
                ->whereMonth('rated_at', now()->month)
                ->avg('rating') ?? 0,
            1
        );

        return compact('stats', 'topAgents', 'recentBreaches', 'recentTickets', 'avgRating');
    }

    public function getAgentDashboardData(int $agentId): array
    {
        $row = Ticket::where('assignee_id', $agentId)
            ->selectRaw('
                SUM(CASE WHEN closed_at IS NULL THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN MONTH(closed_at) = ? THEN 1 ELSE 0 END) as closed_this_month,
                SUM(CASE WHEN sla_resolution_breached = 1 AND closed_at IS NULL THEN 1 ELSE 0 END) as sla_breached,
                AVG(CASE WHEN rated_at IS NOT NULL THEN rating END) as avg_rating
            ', [now()->month])
            ->first();

        $stats = [
            'open' => (int) $row->open,
            'closed_this_month' => (int) $row->closed_this_month,
            'sla_breached' => (int) $row->sla_breached,
            'avg_rating' => round($row->avg_rating ?? 0, 1),
        ];

        $recentTickets = Ticket::where('assignee_id', $agentId)
            ->with(['status:id,name,color', 'customer:id,name'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return compact('stats', 'recentTickets');
    }
}
