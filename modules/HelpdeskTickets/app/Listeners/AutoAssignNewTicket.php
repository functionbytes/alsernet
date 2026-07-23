<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Services\AutoAssignmentService;
use Modules\HelpdeskTickets\Events\TicketCreated;
use Modules\HelpdeskTickets\Services\AssignmentService;

/**
 * Auto-assign a freshly created ticket (#78 ve-auto-assign, lado tickets).
 *
 * Comparte el interruptor y la estrategia globales del módulo Helpdesk
 * (AutoAssignmentService: setting editable en runtime con fallback a
 * config('helpdesk.auto_assignment.*'), default off → inerte).
 *
 * Estrategias soportadas en tickets: round_robin, workload/least_load y skills
 * (por competencias detectadas del asunto+descripción). "manual" deja el ticket
 * sin asignar.
 * Idempotente (ignora tickets ya asignados) y nunca re-lanza: un fallo de
 * asignación no puede romper el pipeline de creación de tickets.
 */
class AutoAssignNewTicket implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk';

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private readonly AssignmentService $assignmentService,
    ) {}

    public function handle(TicketCreated $event): void
    {
        [$enabled, $strategy] = $this->globalConfig();

        if (! $enabled) {
            return;
        }

        $ticket = $event->ticket;

        if ($ticket->assignee_id) {
            return;
        }

        try {
            $assignment = match ($strategy) {
                'round_robin' => $this->assignmentService->autoAssignByRoundRobin($ticket),
                'workload', 'least_load' => $this->assignmentService->autoAssignByWorkload($ticket),
                'skills' => $this->assignmentService->autoAssignBySkills($ticket),
                // "manual" no auto-asigna tickets.
                default => null,
            };

            if (! $assignment && $strategy !== 'manual') {
                Log::info('AutoAssignNewTicket: no suitable agent found', [
                    'ticket_id' => $ticket->id,
                    'strategy' => $strategy,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('AutoAssignNewTicket: auto-assignment failed', [
                'ticket_id' => $ticket->id,
                'strategy' => $strategy,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Toggle + estrategia globales. Usa el servicio del módulo Helpdesk
     * (settings en runtime) y degrada al fichero de config si no está disponible.
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

    public function failed(TicketCreated $event, \Throwable $exception): void
    {
        Log::error('AutoAssignNewTicket failed', [
            'ticket_id' => $event->ticket->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
