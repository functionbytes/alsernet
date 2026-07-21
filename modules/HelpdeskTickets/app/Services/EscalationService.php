<?php

namespace Modules\HelpdeskTickets\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\HelpdeskTickets\Mail\TicketEscalatedMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketHistory;
use Modules\HelpdeskTickets\Support\TicketMailRenderer;

class EscalationService
{
    public const REASON_NO_RESPONSE = 'no_response';

    public const REASON_SLA = 'sla';

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
     *
     * Two passes: (1) tickets sin respuesta más horas que el umbral de su
     * prioridad, (2) tickets con el SLA de resolución vencido o próximo a
     * vencer. Un ticket ya escalado sólo se re-escala tras el cooldown y hasta
     * max_escalations niveles (escalation_count).
     *
     * Returns the number of tickets escalated.
     */
    public function checkAndEscalate(): int
    {
        if (! config('helpdesk.escalation.enabled', true)) {
            return 0;
        }

        $count = 0;
        $notifications = [];
        $escalatedIds = [];

        // Pass 1: age-based (too long without escalation for its priority).
        foreach ($this->thresholds() as $prioritySlug => $hours) {
            $tickets = $this->eligibleQuery()
                ->where('priority', $prioritySlug)
                ->where('created_at', '<=', now()->subHours($hours))
                ->cursor();

            foreach ($tickets as $ticket) {
                $notification = $this->tryEscalate($ticket, self::REASON_NO_RESPONSE);

                if ($notification) {
                    $notifications[] = $notification;
                    $escalatedIds[] = $ticket->id;
                    $count++;
                }
            }
        }

        // Pass 2: SLA resolution breached or about to breach.
        if (config('helpdesktickets.escalation.sla_enabled', true)) {
            $dueWithinMinutes = (int) config('helpdesktickets.escalation.sla_due_within_minutes', 60);

            $query = $this->eligibleQuery()
                ->whereIn('priority', array_keys($this->escalationChain))
                ->whereNotNull('sla_resolution_due_at')
                ->where(function (Builder $q) use ($dueWithinMinutes) {
                    $q->where('sla_resolution_breached', true)
                        ->orWhere('sla_resolution_due_at', '<=', now()->addMinutes($dueWithinMinutes));
                });

            if (! empty($escalatedIds)) {
                $query->whereNotIn('id', $escalatedIds);
            }

            foreach ($query->cursor() as $ticket) {
                $notification = $this->tryEscalate($ticket, self::REASON_SLA);

                if ($notification) {
                    $notifications[] = $notification;
                    $count++;
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
     * Base query for tickets that can (still) be escalated: open, below the
     * escalation cap and outside the cooldown window.
     */
    private function eligibleQuery(): Builder
    {
        $cooldownHours = (int) config('helpdesktickets.escalation.cooldown_hours', 24);
        $maxEscalations = (int) config('helpdesktickets.escalation.max_escalations', 3);

        return Ticket::query()
            ->whereNull('closed_at')
            ->where('escalation_count', '<', $maxEscalations)
            ->where(function (Builder $q) use ($cooldownHours) {
                $q->whereNull('escalated_at')
                    ->orWhere('escalated_at', '<=', now()->subHours($cooldownHours));
            });
    }

    /**
     * Escalate a single ticket, swallowing (and logging) per-ticket errors so
     * one bad row does not abort the whole run.
     */
    private function tryEscalate(Ticket $ticket, string $reason): ?array
    {
        try {
            return $this->escalate($ticket, $reason);
        } catch (\Exception $e) {
            Log::error("Failed to escalate ticket #{$ticket->id}: {$e->getMessage()}", [
                'ticket_id' => $ticket->id,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Escalate a single ticket. Returns notification data on success.
     */
    private function escalate(Ticket $ticket, string $reason): ?array
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

        TicketHistory::logAction($ticket, 'escalated', null, [
            'old_priority' => $oldPriority,
            'new_priority' => $nextPriority,
            'reason' => $reason,
            'level' => $ticket->escalation_count,
        ]);

        Log::info("Ticket #{$ticket->id} escalated from {$oldPriority} to {$nextPriority}", [
            'ticket_id' => $ticket->id,
            'escalation_count' => $ticket->escalation_count,
            'reason' => $reason,
        ]);

        return [
            'agent_id' => $ticket->assignee_id,
            'ticket' => $ticket,
            'old_priority' => $oldPriority,
            'new_priority' => $nextPriority,
            'reason' => $reason,
        ];
    }

    /**
     * Send batched escalation notification emails (queued) to the assigned
     * agent and to the manager roles configured for the module.
     *
     * @param  array<int, array<string, mixed>>  $notifications
     */
    private function sendEscalationNotifications(array $notifications): void
    {
        $agentIds = array_filter(array_unique(array_column($notifications, 'agent_id')));
        $agents = empty($agentIds)
            ? collect()
            : User::whereIn('id', $agentIds)->get(['id', 'email'])->keyBy('id');

        $managerEmails = $this->managerRecipients()->pluck('email');

        foreach ($notifications as $notification) {
            /** @var Ticket $ticket */
            $ticket = $notification['ticket'];

            $recipients = $managerEmails->collect();

            $agent = $notification['agent_id'] ? $agents->get($notification['agent_id']) : null;
            if ($agent) {
                $recipients->push($agent->email);
            }

            $recipients = $recipients->filter()->unique()->values();

            if ($recipients->isEmpty()) {
                continue;
            }

            [$subject, $content] = TicketMailRenderer::render(
                'helpdesk_tickets.ticket_escalated',
                [
                    'TICKET_NUMBER' => $ticket->ticket_number,
                    'TICKET_SUBJECT' => e($ticket->subject),
                    'CUSTOMER_NAME' => e($ticket->customer_name ?? 'N/A'),
                    'OLD_PRIORITY' => ucfirst($notification['old_priority']),
                    'NEW_PRIORITY' => ucfirst($notification['new_priority']),
                    'ESCALATED_AT' => ($ticket->escalated_at ?? now())->format('M d, Y H:i'),
                    'TICKET_URL' => route('manager.helpdesk.tickets.show', $ticket->id),
                ],
                'Ticket escalated: #'.$ticket->ticket_number,
            );

            foreach ($recipients as $email) {
                Mail::to($email)->queue(new TicketEscalatedMail($ticket, $subject, $content));
            }
        }
    }

    /**
     * Users that should always be notified of escalations (managers).
     *
     * @return Collection<int, User>
     */
    private function managerRecipients(): Collection
    {
        if (! config('helpdesktickets.escalation.notify_managers', true)) {
            return collect();
        }

        $roles = (array) config('helpdesktickets.escalation.notify_roles', ['manager']);

        if (empty($roles)) {
            return collect();
        }

        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->get(['id', 'email'])
            ->collect();
    }
}
