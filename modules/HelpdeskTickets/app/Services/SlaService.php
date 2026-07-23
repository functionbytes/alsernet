<?php

namespace Modules\HelpdeskTickets\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Events\SlaWarning;
use Modules\HelpdeskTickets\Models\SlaPolicy; // TODO: migrate to TicketSlaPolicy
use Modules\HelpdeskTickets\Models\Ticket;

class SlaService
{
    /**
     * English->Spanish priority slug aliases. Ticket.priority is stored in
     * English (low/medium/high/normal/urgent) while helpdesk_priorities.slug is
     * in Spanish (baja/normal/alta/urgente/critico). 'medium' has no dedicated
     * priority, so it shares the 'normal' policy.
     */
    private const PRIORITY_SLUG_ALIASES = [
        'low' => 'baja',
        'medium' => 'normal',
        'normal' => 'normal',
        'high' => 'alta',
        'urgent' => 'urgente',
        'critical' => 'critico',
    ];

    private const PRIORITY_CACHE_KEY = 'helpdesktickets:priority_slug_map';

    /**
     * Calculate due date for a ticket based on SLA policy
     */
    public function calculateDueDate(Ticket $ticket): ?Carbon
    {
        try {
            $policy = $this->getApplicablePolicy($ticket);

            if (! $policy) {
                return null;
            }

            $hours = $policy->resolution_time_hours ?? 24;
            $dueDate = now();

            if ($policy->business_hours_only) {
                $dueDate = $this->addBusinessHours($dueDate, $hours);
            } else {
                $dueDate = $dueDate->addHours($hours);
            }

            return $dueDate;
        } catch (\Exception $e) {
            Log::error('Error calculating due date', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

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
     * Send warnings for tickets approaching SLA breach
     */
    public function sendWarnings(): int
    {
        try {
            $sentCount = 0;

            Ticket::where('sla_resolution_breached', false)
                ->whereNotNull('sla_resolution_due_at')
                ->whereBetween('sla_resolution_due_at', [now(), now()->addMinutes(30)])
                ->whereNull('closed_at')
                ->cursor()
                ->each(function (Ticket $ticket) use (&$sentCount) {
                    $policy = $this->getApplicablePolicy($ticket);

                    if (! $policy) {
                        return;
                    }

                    $totalMinutes = $ticket->created_at->diffInMinutes($ticket->sla_resolution_due_at);
                    $usedMinutes = $ticket->created_at->diffInMinutes(now());
                    $percentUsed = ($usedMinutes / $totalMinutes) * 100;

                    $warningThreshold = $policy->warning_threshold_percent ?? 80;

                    if ($percentUsed >= $warningThreshold) {
                        // El aviso lo envía el listener SendSlaWarningNotification
                        // (SlaWarningMail) suscrito a SlaWarning — no duplicar aquí.
                        event(new SlaWarning($ticket, round($percentUsed, 2)));

                        $sentCount++;

                        Log::info('SLA warning sent', [
                            'ticket_id' => $ticket->id,
                            'ticket_number' => $ticket->ticket_number,
                            'percent_used' => round($percentUsed, 2),
                        ]);
                    }
                });

            return $sentCount;
        } catch (\Exception $e) {
            Log::error('Error sending SLA warnings', ['error' => $e->getMessage()]);

            return 0;
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

    /**
     * Get applicable SLA policy for a ticket.
     *
     * Ticket.priority is a string slug; the legacy helpdesk_sla_policies table
     * routes by priority_id, so the slug is resolved to its id first. The prior
     * implementation compared the non-existent $ticket->priority_id, which
     * Eloquent turned into `WHERE priority_id IS NULL` — priority was ignored
     * and only a global (priority-less) policy could ever match.
     */
    public function getApplicablePolicy(Ticket $ticket): ?SlaPolicy
    {
        $priorityId = $this->priorityIdForSlug($ticket->priority);

        if ($priorityId !== null) {
            $policy = SlaPolicy::where('priority_id', $priorityId)
                ->where('category_id', $ticket->category_id)
                ->where('is_active', true)
                ->first();

            if ($policy) {
                return $policy;
            }

            $policy = SlaPolicy::where('priority_id', $priorityId)
                ->whereNull('category_id')
                ->where('is_active', true)
                ->first();

            if ($policy) {
                return $policy;
            }
        }

        return SlaPolicy::whereNull('priority_id')
            ->whereNull('category_id')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Resolve a ticket priority slug (English or Spanish) to a
     * helpdesk_priorities id, applying the English->Spanish alias when needed.
     */
    private function priorityIdForSlug(?string $slug): ?int
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        $map = Cache::remember(self::PRIORITY_CACHE_KEY, 300, function (): array {
            return DB::connection('helpdesk')
                ->table('helpdesk_priorities')
                ->pluck('id', 'slug')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        });

        $alias = self::PRIORITY_SLUG_ALIASES[$slug] ?? null;

        return $map[$slug] ?? ($alias !== null ? ($map[$alias] ?? null) : null);
    }

    /**
     * Add business hours to a datetime (O(days) algorithm — no hour-by-hour loop).
     * Business hours: Mon–Fri, 09:00–18:00 local time.
     */
    private function addBusinessHours(Carbon $startDate, int $hours): Carbon
    {
        $date = $this->advanceToBusinessHours($startDate->copy());
        $remaining = $hours;

        while ($remaining > 0) {
            $availableToday = 18 - $date->hour;

            if ($remaining <= $availableToday) {
                $date->addHours($remaining);
                $remaining = 0;
            } else {
                $remaining -= $availableToday;
                $date->addDay()->setTime(9, 0, 0);
                while ($date->isWeekend()) {
                    $date->addDay();
                }
            }
        }

        return $date;
    }

    /**
     * Move a Carbon date to the start of the next valid business slot.
     */
    private function advanceToBusinessHours(Carbon $date): Carbon
    {
        // Skip weekends
        while ($date->isWeekend()) {
            $date->addDay()->setTime(9, 0, 0);
        }

        // Before business hours — move to open
        if ($date->hour < 9) {
            $date->setTime(9, 0, 0);

            return $date;
        }

        // After business hours — move to next business day
        if ($date->hour >= 18) {
            $date->addDay()->setTime(9, 0, 0);
            while ($date->isWeekend()) {
                $date->addDay();
            }
        }

        return $date;
    }
}
