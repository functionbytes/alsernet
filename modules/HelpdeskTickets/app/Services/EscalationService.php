<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Mail\TicketEscalatedMail;
use Modules\HelpdeskTickets\Models\Ticket;

class EscalationService
{
    /**
     * Hours before escalation triggers per priority slug, loaded from config.
     *
     * @return array<string, int>
     */
    private function thresholds(): array
    {
        return [
            'low' => (int) config('helpdesk.escalation.thresholds.low', 48),
            'normal' => (int) config('helpdesk.escalation.thresholds.normal', 24),
            'high' => (int) config('helpdesk.escalation.thresholds.high', 12),
        ];
    }

    /**
     * Priority escalation chain (slug => next slug).
     *
     * @var array<string, string>
     */
    private array $escalationChain = [
        'low' => 'normal',
        'normal' => 'high',
        'high' => 'urgent',
    ];

    /**
     * Check all open tickets and escalate priority if overdue.
     * Returns the number of tickets escalated.
     */
    public function checkAndEscalate(): int
    {
        if (! config('helpdesk.escalation.enabled', true)) {
            return 0;
        }

        $count = 0;
        $notifications = [];

        foreach ($this->thresholds() as $prioritySlug => $hours) {
            $tickets = Ticket::query()
                ->where('priority', $prioritySlug)
                ->whereNull('closed_at')
                ->whereNull('escalated_at')
                ->where('created_at', '<=', now()->subHours($hours))
                ->cursor();

            foreach ($tickets as $ticket) {
                try {
                    $notification = $this->escalate($ticket);
                    if ($notification) {
                        $notifications[] = $notification;
                    }
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Failed to escalate ticket #{$ticket->id}: {$e->getMessage()}", [
                        'ticket_id' => $ticket->id,
                        'exception' => $e,
                    ]);
                }
            }
        }

        // Batch-send escalation emails to avoid N+1
        if (! empty($notifications)) {
            $this->sendEscalationNotifications($notifications);
        }

        return $count;
    }

    /**
     * Escalate a single ticket. Returns notification data if an agent should be notified.
     */
    private function escalate(Ticket $ticket): ?array
    {
        $nextPriority = $this->escalationChain[$ticket->priority] ?? null;

        if (! $nextPriority) {
            return null;
        }

        $oldPriority = $ticket->priority;

        $ticket->update([
            'priority' => $nextPriority,
            'escalated_at' => now(),
            'escalation_count' => $ticket->escalation_count + 1,
        ]);

        Log::info("Ticket #{$ticket->id} escalated from {$oldPriority} to {$nextPriority}", [
            'ticket_id' => $ticket->id,
            'escalation_count' => $ticket->escalation_count,
        ]);

        if ($ticket->assignee_id) {
            return [
                'agent_id' => $ticket->assignee_id,
                'ticket' => $ticket,
                'old_priority' => $oldPriority,
                'new_priority' => $nextPriority,
            ];
        }

        return null;
    }

    /**
     * Send batched escalation notification emails.
     *
     * @param  array<int, array<string, mixed>>  $notifications
     */
    private function sendEscalationNotifications(array $notifications): void
    {
        $agentIds = array_unique(array_column($notifications, 'agent_id'));
        $agents = User::whereIn('id', $agentIds)
            ->get(['id', 'email'])
            ->keyBy('id');

        foreach ($notifications as $notification) {
            $agent = $agents->get($notification['agent_id']);

            if ($agent) {
                Mail::to($agent->email)->queue(
                    new TicketEscalatedMail(
                        $notification['ticket'],
                        $notification['old_priority'],
                        $notification['new_priority']
                    )
                );
            }
        }
    }
}
