<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Collection;
use Modules\Helpdesk\Contracts\TicketServiceContract;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;

/**
 * Fallback implementation used when HelpdeskTickets is uninstalled, disabled,
 * or gated off via config('helpdesk.tickets.enabled') = false.
 * Every method is a safe no-op so Helpdesk views/controllers can call the
 * contract unconditionally without runtime checks.
 */
class NullTicketService implements TicketServiceContract
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function createFromConversation(Conversation $conversation, array $payload = []): ?array
    {
        return null;
    }

    public function createForCustomer(Customer $customer, array $payload = []): ?array
    {
        return null;
    }

    public function getCustomerTickets(Customer $customer, int $limit = 5): Collection
    {
        return collect();
    }

    public function getConversationTickets(Conversation $conversation): Collection
    {
        return collect();
    }

    public function getCategories(): Collection
    {
        return collect();
    }

    public function getTicketDetail(int $ticketId): ?array
    {
        return null;
    }

    public function getDashboardData(): array
    {
        return [
            'stats' => [
                'open' => 0, 'closed_today' => 0, 'created_today' => 0,
                'sla_breached' => 0, 'unassigned' => 0, 'overdue' => 0,
            ],
            'topAgents' => collect(),
            'recentBreaches' => collect(),
            'recentTickets' => collect(),
            'avgRating' => 0.0,
        ];
    }

    public function getAgentDashboardData(int $agentId): array
    {
        return [
            'stats' => ['open' => 0, 'closed_this_month' => 0, 'sla_breached' => 0, 'avg_rating' => 0.0],
            'recentTickets' => collect(),
        ];
    }
}
