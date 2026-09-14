<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Events\TicketUnassigned;
use Modules\HelpdeskTickets\Models\Ticket;

class TicketUpdateService
{
    /**
     * Apply field changes to a ticket, creating activity items for each change.
     *
     * @return array<string> Fields that were actually changed
     */
    public function applyChanges(Ticket $ticket, array $data, User $actor): array
    {
        return DB::transaction(fn () => $this->applyChangesWithinTransaction($ticket, $data, $actor));
    }

    /**
     * @return array<string>
     */
    private function applyChangesWithinTransaction(Ticket $ticket, array $data, User $actor): array
    {
        $changed = [];

        if (isset($data['status_id']) && $data['status_id'] != $ticket->status_id) {
            $oldStatus = $ticket->status;
            $ticket->update(['status_id' => $data['status_id']]);
            $newStatus = $ticket->fresh()->status;

            $ticket->items()->create([
                'type' => 'status_change',
                'user_id' => $actor->id,
                'body' => "Estado cambiado de '{$oldStatus->name}' a '{$newStatus->name}'",
                'metadata' => [
                    'old_status_id' => $oldStatus->id,
                    'new_status_id' => $newStatus->id,
                ],
            ]);

            if ($newStatus->stops_sla_timer && ! $oldStatus->stops_sla_timer) {
                // Bug real: Ticket::pauseSla() re-checks $this->status->stops_sla_timer
                // as a guard, but $ticket->status was already cached above (as
                // $oldStatus) before the status_id update, so without refreshing
                // the relation here that guard silently reads the OLD status and
                // pauseSla() never actually paused the SLA clock.
                $ticket->setRelation('status', $newStatus);
                $ticket->pauseSla();
            } elseif (! $newStatus->stops_sla_timer && $oldStatus->stops_sla_timer) {
                $ticket->resumeSla();
            }

            // broadcast() SOLO envía por websockets (PrivateChannel) -- NUNCA
            // pasa por el Dispatcher normal de Laravel, así que los 4 listeners
            // registrados para TicketStatusChanged en
            // HelpdeskTicketsEventServiceProvider (SendCustomerStatusNotification,
            // RecordTicketHistory, RunAutomationsOnTicketStatusChanged,
            // RecalculateSlaPolicy) nunca corrían para un cambio de estado real
            // (detectado 3-sep-2026 revisando el ciclo de vida del ticket). El
            // evento implementa ShouldBroadcast + Dispatchable, así que
            // ::dispatch() sigue emitiendo por websocket exactamente igual Y
            // además dispara esos 4 listeners.
            TicketStatusChanged::dispatch($ticket, $oldStatus, $newStatus);
            $changed[] = 'status_id';
            unset($data['status_id']);
        }

        if (isset($data['priority']) && $data['priority'] != $ticket->priority) {
            $ticket->items()->create([
                'type' => 'priority_changed',
                'user_id' => $actor->id,
                'body' => "Prioridad cambiada de '{$ticket->priority}' a '{$data['priority']}'",
                'metadata' => ['old' => $ticket->priority, 'new' => $data['priority']],
            ]);
            $changed[] = 'priority';
        }

        if (isset($data['category_id']) && $data['category_id'] != $ticket->category_id) {
            $oldCategory = $ticket->category;
            $ticket->update(['category_id' => $data['category_id']]);
            $newCategory = $ticket->fresh()->category;

            $ticket->items()->create([
                'type' => 'category_changed',
                'user_id' => $actor->id,
                'body' => "Categoría cambiada de '{$oldCategory->name}' a '{$newCategory->name}'",
                'metadata' => ['old' => $ticket->category_id, 'new' => $data['category_id']],
            ]);
            $changed[] = 'category_id';
            unset($data['category_id']);
        }

        // Bug real: array_key_exists (no isset) es obligatorio aquí. Con isset(),
        // pasar 'assignee_id' => null (desasignar) se trataba como "no viene en
        // $data" y este bloque entero se saltaba, dejando la rama de
        // desasignación (más abajo) inalcanzable: nunca se limpiaba
        // assignee_id/assigned_at ni se disparaba TicketUnassigned.
        if (array_key_exists('assignee_id', $data) && $data['assignee_id'] != $ticket->assignee_id) {
            if ($data['assignee_id']) {
                $ticket->assignTo($data['assignee_id']);

                // Bug real: reasignar desde el formulario de edición de la
                // ficha nunca disparaba TicketAssigned (a diferencia de
                // AssignmentService::assignTicket(), que sí lo hace), así que
                // NotifyAgentOfAssignment/RunAutomationsOnTicketAssigned no
                // corrían al cambiar el agente desde aquí.
                $agent = User::find($data['assignee_id']);
                if ($agent) {
                    TicketAssigned::dispatch($ticket, $agent);
                }
            } else {
                $ticket->update(['assignee_id' => null, 'assigned_at' => null]);
                $ticket->items()->create([
                    'type' => 'unassigned',
                    'user_id' => $actor->id,
                    'body' => 'Ticket desasignado',
                ]);

                TicketUnassigned::dispatch($ticket);
            }
            $changed[] = 'assignee_id';
            unset($data['assignee_id']);
        }

        $remaining = array_diff_key($data, array_flip(['status_id', 'category_id', 'assignee_id']));
        if (! empty($remaining)) {
            $ticket->update($remaining);
            $changed = array_merge($changed, array_keys($remaining));
        }

        return $changed;
    }
}
