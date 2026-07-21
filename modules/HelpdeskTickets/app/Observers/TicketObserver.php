<?php

namespace Modules\HelpdeskTickets\Observers;

use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Modules\HelpdeskTickets\Support\ReportsCache;

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

        TicketHistory::logTicketCreated($ticket, auth()->user());
    }

    public function saved(Ticket $ticket): void
    {
        ReportsCache::bump();
    }

    public function deleted(Ticket $ticket): void
    {
        ReportsCache::bump();
    }

    public function updated(Ticket $ticket): void
    {
        $changes = [];

        foreach ($ticket->getDirty() as $field => $newValue) {
            if (in_array($field, ['updated_at', 'created_at', 'deleted_at'], true)) {
                continue;
            }

            $oldValue = $ticket->getOriginal($field);

            if ($field === 'status_id' && $oldValue !== $newValue) {
                $this->logStatusChange($ticket, $oldValue, $newValue);

                continue;
            }

            if ($field === 'assignee_id') {
                $this->logAssigneeChange($ticket, $oldValue, $newValue);

                continue;
            }

            $changes[] = ['field' => $field, 'old' => $oldValue, 'new' => $newValue];
        }

        // Single batch INSERT instead of one query per dirty field.
        TicketHistory::insertFieldChanges($ticket, $changes, auth()->user());
    }

    private function logStatusChange(Ticket $ticket, mixed $oldValue, mixed $newValue): void
    {
        $statusIds = array_filter([$oldValue, $newValue]);
        $statuses = TicketStatus::whereIn('id', $statusIds)->get()->keyBy('id');
        $oldStatus = $statuses[$oldValue] ?? null;
        $newStatus = $statuses[$newValue] ?? null;

        if ($oldStatus && $newStatus) {
            TicketHistory::logStatusChange($ticket, $oldStatus, $newStatus, auth()->user());
        }
    }

    private function logAssigneeChange(Ticket $ticket, mixed $oldValue, mixed $newValue): void
    {
        if ($newValue && ! $oldValue) {
            $ticket->loadMissing('assignee');
            TicketHistory::logAssigned($ticket, auth()->user(), $ticket->assignee);

            return;
        }

        if (! $newValue && $oldValue) {
            TicketHistory::logUnassigned($ticket, auth()->user());
        }
    }
}
