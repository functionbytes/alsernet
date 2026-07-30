<?php

namespace Modules\HelpdeskTickets\Services;

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
                'assign_user' => $ticket->update(['assignee_id' => $value]),
                'set_priority' => $ticket->update(['priority' => $value]),
                'set_status' => $ticket->update(['status_id' => $value]),
                'add_tag' => $ticket->update([
                    'tags' => array_unique(array_merge($ticket->tags ?? [], [$value])),
                ]),
                'close' => $ticket->update(['closed_at' => now()]),
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

    private function notifyAgent(Ticket $ticket): void
    {
        $agent = $ticket->assignee;

        if (! $agent) {
            return;
        }

        $agent->notify(new AutomationTicketNotification($ticket));
    }
}
