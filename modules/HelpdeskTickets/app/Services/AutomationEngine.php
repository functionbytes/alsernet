<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Modules\HelpdeskTickets\Events\TicketAssigned;
use Modules\HelpdeskTickets\Events\TicketClosed;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Notifications\AutomationTicketNotification;

class AutomationEngine
{
    public function handle(string $event, Ticket $ticket): void
    {
        $automations = Automation::query()
            ->where('trigger_event', $event)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        foreach ($automations as $automation) {
            if (! $this->matchesConditions($automation->conditions, $ticket)) {
                continue;
            }

            $this->runActions($automation->actions, $ticket);
            $automation->increment('run_count');
            $automation->update(['last_run_at' => now()]);
        }
    }

    private function matchesConditions(array $conditions, Ticket $ticket): bool
    {
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $op = $condition['op'] ?? 'equals';
            $value = $condition['value'] ?? null;
            $ticketValue = data_get($ticket, $field);

            $matches = match ($op) {
                'equals' => $ticketValue == $value,
                'not_equals' => $ticketValue != $value,
                'contains' => is_string($ticketValue) && str_contains($ticketValue, $value),
                'not_contains' => is_string($ticketValue) && ! str_contains($ticketValue, $value),
                'in' => is_array($value) && in_array($ticketValue, $value, false),
                'greater_than' => $ticketValue > $value,
                'less_than' => $ticketValue < $value,
                'is_null' => $ticketValue === null,
                'is_not_null' => $ticketValue !== null,
                default => false,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    private function runActions(array $actions, Ticket $ticket): void
    {
        foreach ($actions as $action) {
            $type = $action['type'] ?? null;
            $value = $action['value'] ?? null;

            match ($type) {
                'assign_group' => $ticket->update(['group_id' => $value]),
                // Antes hacía update() directo: no creaba el item de
                // actividad ni disparaba TicketAssigned, así que un
                // automatismo que asignaba agente no notificaba al agente
                // (NotifyAgentOfAssignment) ni encadenaba otros automatismos
                // "al asignar" (RunAutomationsOnTicketAssigned) — a
                // diferencia de la asignación manual/AssignmentService, que
                // sí lo hacían.
                'assign_user' => $this->assignUser($ticket, $value),
                'set_priority' => $ticket->update(['priority' => $value]),
                'set_status' => $ticket->update(['status_id' => $value]),
                'add_tag' => $ticket->update([
                    'tags' => array_unique(array_merge($ticket->tags ?? [], [$value])),
                ]),
                // Antes hacía update(['closed_at' => now()]) sin tocar
                // status_id: reproducía el mismo bug ya arreglado para el
                // botón manual "Cerrar ticket" (ver docblock de
                // Ticket::close()) — el ticket quedaba con closed_at pero
                // seguía apareciendo "Abierto" en los listados. Tampoco
                // disparaba TicketClosed, así que la encuesta CSAT
                // automática no se enviaba desde un cierre por regla.
                'close' => $this->closeTicket($ticket),
                'add_internal_note' => $ticket->items()->create([
                    'type' => 'message',
                    'body' => $value,
                    'is_internal' => true,
                ]),
                'notify_agent' => $this->notifyAgent($ticket),
                default => null,
            };
        }
    }

    private function assignUser(Ticket $ticket, mixed $value): void
    {
        $agent = User::find($value);

        if (! $agent) {
            return;
        }

        $ticket->assignTo($agent->id);

        TicketAssigned::dispatch($ticket, $agent);
    }

    private function closeTicket(Ticket $ticket): void
    {
        $ticket->close();

        TicketClosed::dispatch($ticket);
    }

    private function notifyAgent(Ticket $ticket): void
    {
        $agent = $ticket->assignee;

        if (! $agent) {
            return;
        }

        $agent->notify(new AutomationTicketNotification($ticket));
    }
}
