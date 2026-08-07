<?php

namespace Modules\HelpdeskTickets\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Models\Ticket;

class SlaService
{
    /**
     * Check for SLA breaches and mark them
     */
    public function checkBreaches(): Collection
    {
        try {
            $breachedTickets = collect();

            Ticket::where('sla_resolution_breached', false)
                ->whereNotNull('sla_resolution_due_at')
                ->where('sla_resolution_due_at', '<', now())
                ->whereNull('closed_at')
                ->cursor()
                ->each(function (Ticket $ticket) use ($breachedTickets) {
                    // updateQuietly: es un sweep por lotes, no queremos disparar
                    // los observers del ticket (historial, embeddings…) por cada
                    // fila solo por marcar el flag. El aviso va por el evento SLA.
                    $ticket->updateQuietly(['sla_resolution_breached' => true]);

                    // El aviso de incumplimiento lo envía el listener
                    // SendSlaBreachNotification (SlaBreachMail) — no duplicar aquí.
                    event(new SlaBreached($ticket));

                    Log::warning('SLA breach detected', [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'due_at' => $ticket->sla_resolution_due_at,
                    ]);

                    $breachedTickets->push($ticket);
                });

            return $breachedTickets;
        } catch (\Exception $e) {
            Log::error('Error checking SLA breaches', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * Get tickets that have breached SLA
     */
    public function getBreachedTickets(?int $agentId = null): Collection
    {
        $query = Ticket::where('sla_resolution_breached', true)
            ->whereNull('closed_at');

        if ($agentId) {
            $query->where('assignee_id', $agentId);
        }

        return $query->orderBy('sla_resolution_due_at', 'asc')->get();
    }

    /**
     * Get tickets approaching SLA breach
     */
    public function getUpcomingBreaches(int $hoursAhead = 24): Collection
    {
        return Ticket::where('sla_resolution_breached', false)
            ->whereNotNull('sla_resolution_due_at')
            ->whereBetween('sla_resolution_due_at', [now(), now()->addHours($hoursAhead)])
            ->whereNull('closed_at')
            ->orderBy('sla_resolution_due_at', 'asc')
            ->get();
    }

    /**
     * Calculate response time in minutes
     */
    public function calculateResponseTime(Ticket $ticket): ?int
    {
        if (! $ticket->first_response_at) {
            return null;
        }

        return $ticket->created_at->diffInMinutes($ticket->first_response_at);
    }

    /**
     * Calculate resolution time in minutes
     */
    public function calculateResolutionTime(Ticket $ticket): ?int
    {
        if (! $ticket->closed_at) {
            return null;
        }

        return $ticket->created_at->diffInMinutes($ticket->closed_at);
    }

    /**
     * Pause SLA for a ticket (e.g., waiting for customer response).
     */
    public function pauseSla(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at !== null) {
            return;
        }

        $ticket->update(['sla_paused_at' => now()]);
    }

    /**
     * Resume SLA for a ticket. Adds the paused duration to sla_paused_duration_minutes
     * and extends all SLA due dates accordingly.
     */
    public function resumeSla(Ticket $ticket): void
    {
        if ($ticket->sla_paused_at === null) {
            return;
        }

        $pausedMinutes = $ticket->sla_paused_at->diffInMinutes(now());
        $updates = [
            'sla_paused_duration_minutes' => ($ticket->sla_paused_duration_minutes ?? 0) + $pausedMinutes,
            'sla_paused_at' => null,
        ];

        if ($ticket->sla_resolution_due_at) {
            $updates['sla_resolution_due_at'] = $ticket->sla_resolution_due_at->addMinutes($pausedMinutes);
        }

        if ($ticket->sla_first_response_due_at) {
            $updates['sla_first_response_due_at'] = $ticket->sla_first_response_due_at->addMinutes($pausedMinutes);
        }

        $ticket->update($updates);
    }

    /**
     * Get effective SLA resolution due date, accounting for any current pause time.
     */
    public function getEffectiveDueDate(Ticket $ticket): ?Carbon
    {
        if ($ticket->sla_resolution_due_at === null) {
            return null;
        }

        if ($ticket->sla_paused_at !== null) {
            $currentPauseMinutes = $ticket->sla_paused_at->diffInMinutes(now());

            return $ticket->sla_resolution_due_at->copy()->addMinutes($currentPauseMinutes);
        }

        return $ticket->sla_resolution_due_at;
    }
}
