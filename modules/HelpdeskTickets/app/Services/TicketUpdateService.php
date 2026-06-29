<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
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
                $ticket->pauseSla();
            } elseif (! $newStatus->stops_sla_timer && $oldStatus->stops_sla_timer) {
                $ticket->resumeSla();
            }

            broadcast(new TicketStatusChanged($ticket, $oldStatus, $newStatus));
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

        if (isset($data['assignee_id']) && $data['assignee_id'] != $ticket->assignee_id) {
            if ($data['assignee_id']) {
                $ticket->assignTo($data['assignee_id']);
            } else {
                $ticket->update(['assignee_id' => null, 'assigned_at' => null]);
                $ticket->items()->create([
                    'type' => 'unassigned',
                    'user_id' => $actor->id,
                    'body' => 'Ticket desasignado',
                ]);
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
