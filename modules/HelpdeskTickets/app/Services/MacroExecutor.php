<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Macro;
use Modules\HelpdeskTickets\Models\Ticket;

class MacroExecutor
{
    public function run(Macro $macro, Ticket $ticket): void
    {
        DB::connection('helpdesk')->transaction(function () use ($macro, $ticket) {
            foreach ($macro->actions as $action) {
                $this->executeAction($action, $ticket);
            }

            $macro->increment('usage_count');
            $macro->update(['last_used_at' => now()]);
        });
    }

    private function executeAction(array $action, Ticket $ticket): void
    {
        $type = $action['type'] ?? null;
        $value = $action['value'] ?? null;
        $body = $action['body'] ?? null;

        match ($type) {
            'reply' => $ticket->items()->create([
                'type' => 'message',
                'user_id' => auth()->id(),
                'author_id' => auth()->id(),
                'body' => $this->interpolate($body, $ticket),
                'is_internal' => false,
            ]),
            'internal_note' => $ticket->items()->create([
                'type' => 'message',
                'user_id' => auth()->id(),
                'author_id' => auth()->id(),
                'body' => $this->interpolate($body, $ticket),
                'is_internal' => true,
            ]),
            'assign_group' => $ticket->update(['group_id' => $value]),
            'assign_user' => $ticket->update(['assignee_id' => $value]),
            'set_priority' => $ticket->update(['priority' => $value]),
            'set_status' => $ticket->update(['status_id' => $value]),
            'add_tag' => $ticket->update(['tags' => array_unique(array_merge($ticket->tags ?? [], [$value]))]),
            'close' => $ticket->update(['closed_at' => now()]),
            default => null,
        };
    }

    private function interpolate(?string $body, Ticket $ticket): string
    {
        if (! $body) {
            return '';
        }

        return strtr($body, [
            '{{ticket_number}}' => $ticket->ticket_number,
            '{{ticket_title}}' => $ticket->subject ?? $ticket->title ?? '',
            '{{customer_name}}' => $ticket->customer?->name ?? 'Cliente',
            '{{agent_name}}' => auth()->user()?->name ?? 'Agente',
        ]);
    }
}
