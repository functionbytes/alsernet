<?php

namespace Modules\HelpdeskTickets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\AutoAssignmentService;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\AssignmentService;

/**
 * Job to automatically assign unassigned tickets
 */
class AutoAssignUnassignedTickets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public int $backoff = 60;

    public function __construct()
    {
        // Nota: no declarar `public string $queue` — colisiona (tipado) con la
        // propiedad sin tipo del trait Queueable y produce un fatal al componer.
        $this->onQueue('helpdesk-scheduled');
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('auto-assign-tickets'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AutoAssignUnassignedTickets job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(AssignmentService $assignmentService): void
    {
        try {
            Log::info('AutoAssignUnassignedTickets job started at '.now());

            [$enabled, $strategy] = $this->globalConfig();

            if (! $enabled) {
                Log::info('Auto-assignment is disabled. Skipping job.');

                return;
            }

            $unassignedTickets = Ticket::query()
                ->whereNull('assignee_id')
                ->whereNull('closed_at')
                ->orderBy('created_at', 'asc')
                ->cursor();

            $assignedCount = 0;

            foreach ($unassignedTickets as $ticket) {
                try {
                    $assignedAgent = null;

                    if ($strategy === 'round_robin') {
                        $assignedAgent = $assignmentService->autoAssignByRoundRobin($ticket);
                    } elseif ($strategy === 'workload' || $strategy === 'least_load') {
                        $assignedAgent = $assignmentService->autoAssignByWorkload($ticket);
                    } elseif ($strategy === 'skills') {
                        $assignedAgent = $assignmentService->autoAssignBySkills($ticket);
                    } else {
                        Log::warning("Unknown auto-assignment strategy: {$strategy}");

                        continue;
                    }

                    if ($assignedAgent) {
                        // TicketAssigned event is already fired inside AssignmentService
                        Log::info("Auto-assigned ticket #{$ticket->id} to agent #{$assignedAgent->assigned_to}", [
                            'ticket_id' => $ticket->id,
                            'ticket_subject' => $ticket->subject,
                            'agent_id' => $assignedAgent->assigned_to,
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

    /**
     * Toggle + estrategia globales (#78): setting editable en runtime del módulo
     * Helpdesk, con fallback al fichero de config si no está disponible.
     *
     * @return array{0: bool, 1: string}
     */
    private function globalConfig(): array
    {
        try {
            $core = app(AutoAssignmentService::class);

            return [$core->isEnabled(), $core->config()['strategy']];
        } catch (\Throwable) {
            return [
                (bool) config('helpdesk.auto_assignment.enabled', false),
                (string) config('helpdesk.auto_assignment.strategy', 'round_robin'),
            ];
        }
    }
}
