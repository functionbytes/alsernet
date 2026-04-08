<?php

namespace Modules\Helpdesk\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\TicketAssigned;
use Modules\Helpdesk\Events\TicketUnassigned;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketAssignment;
use Modules\Helpdesk\Models\TicketHistory;

class AssignmentService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Assign a ticket to an agent
     */
    public function assignTicket(Ticket $ticket, int $agentId, ?string $reason = null): TicketAssignment
    {
        try {
            $agent = User::findOrFail($agentId);

            if (! $agent->hasRole('helpdesk-agent')) {
                throw new \Exception('User is not a helpdesk agent');
            }

            return DB::transaction(function () use ($ticket, $agentId, $agent, $reason) {
                if ($ticket->assignee_id && $ticket->assignee_id !== $agentId) {
                    $this->unassignTicket($ticket, 'Reassigning to another agent');
                }

                $assignment = TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'agent_id' => $agentId,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'notes' => $reason,
                ]);

                $ticket->update([
                    'assignee_id' => $agentId,
                    'assigned_at' => now(),
                ]);

                TicketHistory::logAssigned($ticket, auth()->user(), $agent);

                event(new TicketAssigned($ticket, $agent));

                $this->notificationService->notifyTicketAssigned($ticket, $agent);

                Log::info('Ticket assigned', [
                    'ticket_id' => $ticket->id,
                    'agent_id' => $agentId,
                    'reason' => $reason,
                ]);

                return $assignment;
            });
        } catch (\Exception $e) {
            Log::error('Error assigning ticket', [
                'ticket_id' => $ticket->id,
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Unassign a ticket from its current agent
     */
    public function unassignTicket(Ticket $ticket, ?string $reason = null): void
    {
        try {
            if (! $ticket->assignee_id) {
                return;
            }

            DB::transaction(function () use ($ticket, $reason) {
                TicketAssignment::create([
                    'ticket_id' => $ticket->id,
                    'agent_id' => $ticket->assignee_id,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => $ticket->assigned_at,
                    'unassigned_at' => now(),
                    'notes' => $reason,
                ]);

                $oldAgentId = $ticket->assignee_id;

                $ticket->update([
                    'assignee_id' => null,
                    'assigned_at' => null,
                ]);

                TicketHistory::logUnassigned($ticket, auth()->user());

                event(new TicketUnassigned($ticket));

                Log::info('Ticket unassigned', [
                    'ticket_id' => $ticket->id,
                    'old_agent_id' => $oldAgentId,
                    'reason' => $reason,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Error unassigning ticket', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reassign a ticket to a different agent
     */
    public function reassignTicket(Ticket $ticket, int $newAgentId, ?string $reason = null): TicketAssignment
    {
        try {
            $newAgent = User::findOrFail($newAgentId);

            if (! $newAgent->hasRole('helpdesk-agent')) {
                throw new \Exception('User is not a helpdesk agent');
            }

            return DB::transaction(function () use ($ticket, $newAgentId, $reason) {
                if ($ticket->assignee_id) {
                    $this->unassignTicket($ticket, $reason ?? 'Reassigning to another agent');
                }

                return $this->assignTicket($ticket, $newAgentId, $reason);
            });
        } catch (\Exception $e) {
            Log::error('Error reassigning ticket', [
                'ticket_id' => $ticket->id,
                'new_agent_id' => $newAgentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Auto-assign ticket using round-robin strategy
     */
    public function autoAssignByRoundRobin(Ticket $ticket): ?TicketAssignment
    {
        try {
            $agents = $this->getAvailableAgents($ticket->category_id);

            if ($agents->isEmpty()) {
                Log::warning('No available agents for round-robin assignment', [
                    'ticket_id' => $ticket->id,
                ]);

                return null;
            }

            $agentWorkloads = $agents->map(function ($agent) {
                return [
                    'agent_id' => $agent->id,
                    'workload' => $this->getAgentWorkload($agent->id),
                ];
            })->sortBy('workload');

            $minWorkload = $agentWorkloads->first()['workload'];
            $candidateAgents = $agentWorkloads->where('workload', $minWorkload);

            $lastAssignment = TicketAssignment::whereIn('agent_id', $candidateAgents->pluck('agent_id'))
                ->orderByDesc('assigned_at')
                ->first();

            if ($lastAssignment) {
                $lastAgentId = $lastAssignment->agent_id;
                $nextAgent = $candidateAgents->where('agent_id', '>', $lastAgentId)->first()
                    ?? $candidateAgents->first();
                $selectedAgentId = $nextAgent['agent_id'];
            } else {
                $selectedAgentId = $candidateAgents->first()['agent_id'];
            }

            return $this->assignTicket($ticket, $selectedAgentId, 'Auto-assigned by round-robin');
        } catch (\Exception $e) {
            Log::error('Error in round-robin assignment', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Auto-assign ticket based on agent workload
     */
    public function autoAssignByWorkload(Ticket $ticket): ?TicketAssignment
    {
        try {
            $agents = $this->getAvailableAgents($ticket->category_id);

            if ($agents->isEmpty()) {
                Log::warning('No available agents for workload assignment', [
                    'ticket_id' => $ticket->id,
                ]);

                return null;
            }

            $agentWorkloads = $agents->map(function ($agent) {
                $openTickets = Ticket::where('assignee_id', $agent->id)
                    ->whereNull('closed_at')
                    ->with('priority')
                    ->get();

                $totalWorkload = $openTickets->sum(function ($ticket) {
                    return $ticket->priority->resolution_time_hours ?? 24;
                });

                return [
                    'agent_id' => $agent->id,
                    'workload' => $totalWorkload,
                ];
            })->sortBy('workload');

            $selectedAgentId = $agentWorkloads->first()['agent_id'];

            return $this->assignTicket($ticket, $selectedAgentId, 'Auto-assigned by workload');
        } catch (\Exception $e) {
            Log::error('Error in workload assignment', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get current workload (ticket count) for an agent
     */
    public function getAgentWorkload(int $agentId): int
    {
        return Ticket::where('assignee_id', $agentId)
            ->whereNull('closed_at')
            ->count();
    }

    /**
     * Get available agents, optionally filtered by category
     */
    public function getAvailableAgents(?int $categoryId = null): Collection
    {
        $query = User::whereHas('roles', function ($q) {
            $q->where('name', 'helpdesk-agent');
        })->where('is_active', true);

        if ($categoryId) {
            $query->whereHas('agentCategories', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        return $query->orderBy('name')->get();
    }
}
