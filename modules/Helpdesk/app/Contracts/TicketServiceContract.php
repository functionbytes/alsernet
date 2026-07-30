<?php

namespace Modules\Helpdesk\Contracts;

use Illuminate\Support\Collection;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;

/**
 * Bridge contract between Helpdesk and the optional HelpdeskTickets module.
 * Implementations live in HelpdeskTickets (concrete) and Helpdesk (null fallback).
 * Bound conditionally in HelpdeskServiceProvider based on helpdesk_tickets_enabled().
 */
interface TicketServiceContract
{
    /**
     * Whether the Tickets integration is currently usable.
     */
    public function isAvailable(): bool;

    /**
     * Build a ticket from the context of an existing conversation.
     * Returns the new ticket as an associative array (id, ticket_number, url),
     * or null if creation failed or the integration is unavailable.
     *
     * @return array{id:int,ticket_number:string,url:string}|null
     */
    public function createFromConversation(Conversation $conversation, array $payload = []): ?array;

    /**
     * Create a ticket directly for a customer (no originating conversation).
     * Returns the same compact shape as createFromConversation, or null if
     * creation failed or the integration is unavailable.
     *
     * @param  array{subject?: string, message?: string, description?: string, priority?: string, category_id?: int|null, assignee_id?: int|null}  $payload
     * @return array{id: int, ticket_number: string, subject: string, status: ?string, priority: ?string, url: string}|null
     */
    public function createForCustomer(Customer $customer, array $payload = []): ?array;

    /**
     * Latest tickets owned by a customer, sorted by most recent.
     */
    public function getCustomerTickets(Customer $customer, int $limit = 5): Collection;

    /**
     * Tickets created from (linked to) a given conversation. Enables the inbox
     * to surface the tickets that originated in the current conversation.
     *
     * @return Collection<int, mixed>
     */
    public function getConversationTickets(Conversation $conversation): Collection;

    /**
     * Lightweight list of categories for ticket creation forms.
     * Each item: ['id' => int, 'name' => string].
     *
     * @return Collection<int, array{id:int,name:string}>
     */
    public function getCategories(): Collection;

    /**
     * Detail payload for a ticket linked to a conversation.
     * Returns null if not found / not accessible / integration unavailable.
     */
    public function getTicketDetail(int $ticketId): ?array;

    /**
     * Aggregated ticket data for the Helpdesk dashboard.
     * Shape:
     *   stats: array{open,closed_today,created_today,sla_breached,unassigned,overdue}
     *   topAgents: Collection<User-rows with open_tickets,closed_today>
     *   recentBreaches: Collection<Ticket>
     *   recentTickets: Collection<Ticket>
     *   avgRating: float
     */
    public function getDashboardData(): array;

    /**
     * Per-agent ticket data for the Agents detail view.
     * Shape:
     *   stats: array{open,closed_this_month,sla_breached,avg_rating}
     *   recentTickets: Collection<Ticket>
     */
    public function getAgentDashboardData(int $agentId): array;
}
