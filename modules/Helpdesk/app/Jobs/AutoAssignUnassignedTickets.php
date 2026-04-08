<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Services\AssignmentService;

/**
 * Job to automatically assign unassigned tickets
 */
class AutoAssignUnassignedTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public string $queue = 'helpdesk-scheduled';

    public function middleware(): array
    {
        return [(new WithoutOverlapping('auto-assign-tickets'))->dontRelease()];
    }

    /**
     * Execute the job.
     */
    public function handle(AssignmentService $assignmentService): void
    {
        try {
            Log::info('AutoAssignUnassignedTickets job started at '.now());

            if (! config('helpdesk.auto_assignment.enabled', false)) {
                Log::info('Auto-assignment is disabled. Skipping job.');

                return;
            }

            $strategy = config('helpdesk.auto_assignment.strategy', 'round_robin');

            $unassignedTickets = Ticket::query()
                ->whereNull('assignee_id')
                ->whereNull('closed_at')
                ->orderBy('created_at', 'asc')
                ->get();

            $assignedCount = 0;

            foreach ($unassignedTickets as $ticket) {
                try {
                    $assignedAgent = null;

                    if ($strategy === 'round_robin') {
                        $assignedAgent = $assignmentService->autoAssignByRoundRobin($ticket);
                    } elseif ($strategy === 'workload') {
                        $assignedAgent = $assignmentService->autoAssignByWorkload($ticket);
                    } else {
                        Log::warning("Unknown auto-assignment strategy: {$strategy}");

                        continue;
                    }

                    if ($assignedAgent) {
                        // TicketAssigned event is already fired inside AssignmentService
                        Log::info("Auto-assigned ticket #{$ticket->id} to agent #{$assignedAgent->agent_id}", [
                            'ticket_id' => $ticket->id,
                            'ticket_subject' => $ticket->subject,
                            'agent_id' => $assignedAgent->agent_id,
                            'strategy' => $strategy,
                            'assigned_at' => now(),
                        ]);

                        $assignedCount++;
                    } else {
                        Log::warning("Failed to auto-assign ticket #{$ticket->id} - No suitable agent found", [
                            'ticket_id' => $ticket->id,
                            'strategy' => $strategy,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to auto-assign ticket #{$ticket->id}: {$e->getMessage()}", [
                        'ticket_id' => $ticket->id,
                        'exception' => $e,
                    ]);
                }
            }

            Log::info('AutoAssignUnassignedTickets job completed at '.now()." - Total tickets assigned: {$assignedCount}", [
                'assigned_count' => $assignedCount,
                'total_unassigned' => $unassignedTickets->count(),
                'strategy' => $strategy,
            ]);
        } catch (\Exception $e) {
            Log::error("AutoAssignUnassignedTickets job failed: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
