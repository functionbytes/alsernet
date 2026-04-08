<?php

namespace Modules\Helpdesk\Listeners;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Events\SlaWarning;
use Modules\Helpdesk\Mail\SlaWarningMail;

/**
 * Send warning notification when ticket SLA is approaching breach
 */
class SendSlaWarningNotification implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct()
    {
        $this->queue = 'notifications';
    }

    public int $timeout = 60;

    public array $backoff = [30, 60, 120];

    /**
     * Handle the event
     */
    public function handle(SlaWarning $event): void
    {
        $ticket = $event->ticket;

        if (! $ticket->assignedAgent) {
            Log::warning('Cannot send SLA warning - no agent assigned', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $agent = $ticket->assignedAgent;
        $timeRemaining = now()->diff($ticket->due_at);

        Log::info('Sending SLA warning notification', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'due_at' => $ticket->due_at,
            'time_remaining' => $timeRemaining->format('%h horas %i minutos'),
        ]);

        try {
            Mail::to($agent->email, $agent->name)->queue(new SlaWarningMail($ticket, $event->percentUsed ?? 0));
        } catch (\Throwable $e) {
            Log::error('Helpdesk notification failed', [
                'listener' => static::class,
                'agent_id' => $agent->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
