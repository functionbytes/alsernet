<?php

namespace Modules\HelpdeskTickets\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Services\CatalogCacheService;

class TicketObserver
{
    public function creating(Ticket $ticket): void
    {
        if (! $ticket->ticket_number) {
            $ticket->ticket_number = Ticket::generateTicketNumber();
        }

        if (! $ticket->status_id) {
            $default = CatalogCacheService::defaultStatus();
            if ($default) {
                $ticket->status_id = $default->id;
            }
        }
    }

    public function created(Ticket $ticket): void
    {
        if ($ticket->sla_policy_id) {
            $ticket->calculateSlaDueDates();
        }
    }

    public function saved(Ticket $ticket): void
    {
        Cache::forget('helpdesk:reports');
    }

    public function deleted(Ticket $ticket): void
    {
        Cache::forget('helpdesk:reports');
    }

    public function updated(Ticket $ticket): void
    {
        // status_id and assignee_id are tracked by RecordTicketHistory listener
        // (via TicketStatusChanged and TicketAssigned events) to avoid duplicates.
        $skip = ['updated_at', 'created_at', 'deleted_at', 'status_id', 'assignee_id'];

        foreach ($ticket->getDirty() as $field => $newValue) {
            if (in_array($field, $skip)) {
                continue;
            }

            TicketHistory::logFieldChange(
                $ticket,
                $field,
                $ticket->getOriginal($field),
                $newValue,
                auth()->user()
            );
        }
    }
}
