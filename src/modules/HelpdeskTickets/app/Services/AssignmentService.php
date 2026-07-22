<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\SkillsRoutingService;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketUnassigned;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAssignment;
use Modules\HelpdeskTickets\Models\TicketHistory;

class AssignmentService
{
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
                    'assigned_to' => $agentId,
                    // null cuando asigna el sistema (auto-asignación en cola).
                    'assigned_by' => auth()->id(),
                    'assigned_at' => now(),
                    'reason' => $reason,
                ]);

                $ticket->update([
                    'assignee_id' => $agentId,
                    'assigned_at' => now(),
                ]);

                TicketHistory::logAssigned($ticket, auth()->user(), $agent);

                // El email al agente lo envía el listener NotifyAgentOfAssignment
                // (TicketAssignedMail) suscrito a este evento — no duplicar aquí.
                event(new TicketAssigned($ticket, $agent));

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
                    'assigned_to' => $ticket->assignee_id,
                    'assigned_by' => auth()->id(),
                    'assigned_at' => $ticket->assigned_at ?? now(),
                    'unassigned_at' => now(),
                    'reason' => $reason,
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

            $workloads = Ticket::whereNull('closed_at')
                ->selectRaw('assignee_id, COUNT(*) as workload')
                ->groupBy('assignee_id')
                ->pluck('workload', 'assignee_id');

            $agentWorkloads = $agents->map(fn ($agent) => [
                'agent_id' => $agent->id,
                'workload' => $workloads[$agent->id] ?? 0,
            ])->sortBy('workload');

            $minWorkload = $agentWorkloads->first()['workload'];
            $candidateAgents = $agentWorkloads->where('workload', $minWorkload);

            $lastAssignment = TicketAssignment::whereIn('assigned_to', $candidateAgents->pluck('agent_id'))
                ->orderByDesc('assigned_at')
                ->first();

            if ($lastAssignment) {
                $lastAgentId = $lastAssignment->assigned_to;
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

            $openTickets = Ticket::whereIn('assignee_id', $agents->pluck('id'))
                ->whereNull('closed_at')
                ->get()
                ->groupBy('assignee_id');

            // `priority` es un slug (urgent/high/normal/low), no una relación:
            // pondera la carga abierta según el peso de cada prioridad.
            $weights = ['urgent' => 4, 'high' => 3, 'normal' => 2, 'low' => 1];

            $agentWorkloads = $agents->map(function ($agent) use ($openTickets, $weights) {
                $tickets = $openTickets[$agent->id] ?? collect();
                $totalWorkload = $tickets->sum(fn ($ticket) => $weights[$ticket->priority] ?? 2);

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
        })->where('available', true);

        if ($categoryId) {
            $query->whereHas('agentCategories', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $agents = $query->orderBy('firstname')->get();

        return $this->filterByAvailability($agents);
    }

    /**
     * Keep only agents that can receive automatic work right now according to
     * their helpdesk AgentSettings (availability, vacation, capacity, presence).
     * Agents without a settings row are kept (non-regressing default), and any
     * failure in the availability layer degrades to "everyone available".
     */
    private function filterByAvailability(Collection $agents): Collection
    {
        if ($agents->isEmpty() || ! class_exists(SkillsRoutingService::class)) {
            return $agents;
        }

        try {
            $availableIds = app(SkillsRoutingService::class)
                ->filterAvailableAgents($agents->pluck('id')->all());

            return $agents->whereIn('id', $availableIds)->values();
        } catch (\Throwable $e) {
            Log::warning('AssignmentService: availability filter failed, keeping all agents', [
                'error' => $e->getMessage(),
            ]);

            return $agents;
        }
    }
}
