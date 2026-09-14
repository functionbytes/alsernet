<?php

namespace Modules\HelpdeskTickets\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\TicketSlaBreached;
use Modules\HelpdeskTickets\Notifications\TicketSlaBreached as TicketSlaBreachedNotification;
use Modules\HelpdeskTickets\Services\TeamChannelNotifier;

class SendSlaBreachBroadcastNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'helpdesk-events';

    public int $tries = 3;

    public array $backoff = [5, 15, 30];

    public function __construct(private readonly TeamChannelNotifier $teamChannels) {}

    public function handle(TicketSlaBreached $event): void
    {
        $ticket = $event->ticket;
        $assignee = $ticket->assignee;

        if (! $assignee) {
            Log::warning('SLA breach: no assignee to notify', ['ticket_id' => $ticket->id]);
        } else {
            $assignee->notify(new TicketSlaBreachedNotification($ticket, $event->breach));

            Log::info('SLA breach notification sent', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'agent_id' => $assignee->id,
                'breach_type' => $event->breach->breach_type,
            ]);
        }

        // Modal 31: aviso de equipo (Slack/Teams), OFF por defecto sin
        // webhook configurado. Se manda haya o no assignee al que avisar en
        // el panel — un SLA roto sin nadie asignado es justo el caso que más
        // le interesa al canal del equipo.
        $this->teamChannels->notify(
            "⚠️ SLA incumplido — Ticket #{$ticket->ticket_number}: {$event->breach->breach_type_label}"
        );
    }

    public function failed(TicketSlaBreached $event, \Throwable $exception): void
    {
        Log::error('SendSlaBreachBroadcastNotification failed', [
            'ticket_id' => $event->ticket->id ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
