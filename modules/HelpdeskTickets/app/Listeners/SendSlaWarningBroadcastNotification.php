<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketSlaNearBreach;
use Modules\HelpdeskTickets\Notifications\TicketSlaNearBreach as TicketSlaNearBreachNotification;

class SendSlaWarningBroadcastNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function handle(TicketSlaNearBreach $event): void
    {
        $ticket = $event->ticket;
        $assignee = $ticket->assignee;

        if (! $assignee) {
            Log::warning('SLA warning: no assignee to notify', ['ticket_id' => $ticket->id]);

            return;
        }

        $assignee->notify(new TicketSlaNearBreachNotification($ticket, $event->breachType, $event->remainingMinutes));

        Log::info('SLA warning notification sent', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'agent_id' => $assignee->id,
            'breach_type' => $event->breachType,
            'remaining_minutes' => $event->remainingMinutes,
        ]);
    }

    public function failed(TicketSlaNearBreach $event, \Throwable $exception): void
    {
        Log::error('SendSlaWarningBroadcastNotification failed', [
            'ticket_id' => $event->ticket->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
