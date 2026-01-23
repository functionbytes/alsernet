<?php

namespace Modules\Document\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Models\TicketSlaBreach;

class CheckSlaBreaches extends Command
{
    protected $signature = 'tickets:check-sla-breaches';

    protected $description = 'Verificar incumplimientos de SLA en tickets abiertos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Verificando incumplimientos de SLA...');

        $now = Carbon::now();
        $breachesFound = 0;

        // Check first response SLA breaches
        $breachesFound += $this->checkFirstResponseBreaches($now);

        // Check resolution SLA breaches
        $breachesFound += $this->checkResolutionBreaches($now);

        $this->info("Se detectaron {$breachesFound} incumplimientos de SLA.");

        return 0;
    }

    /**
     * Check for first response SLA breaches
     */
    protected function checkFirstResponseBreaches(Carbon $now): int
    {
        $breaches = 0;

        // Find tickets that have breached first response SLA
        $tickets = Ticket::whereNull('first_response_at')
            ->where('sla_first_response_breached', false)
            ->whereNotNull('sla_first_response_due_at')
            ->where('sla_first_response_due_at', '<', $now)
            ->where('sla_paused', false) // Don't check paused tickets
            ->with(['status', 'category', 'assignee', 'group', 'slaPolicy'])
            ->get();

        foreach ($tickets as $ticket) {
            $this->info("Ticket #{$ticket->ticket_number}: Incumplimiento de primera respuesta");

            // Calculate breach duration
            $breachDuration = $ticket->sla_first_response_due_at->diffInMinutes($now);

            // Mark ticket as breached
            $ticket->update([
                'sla_first_response_breached' => true,
            ]);

            // Create breach record
            $breach = TicketSlaBreach::create([
                'ticket_id' => $ticket->id,
                'breach_type' => 'first_response',
                'due_at' => $ticket->sla_first_response_due_at,
                'breached_at' => $now,
                'breach_duration_minutes' => $breachDuration,
                'resolved' => false,
                'metadata' => [
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status->name,
                    'category' => $ticket->category->name,
                    'assignee' => $ticket->assignee?->name,
                    'group' => $ticket->group?->name,
                    'priority' => $ticket->priority,
                    'sla_policy' => $ticket->slaPolicy?->name,
                ],
            ]);

            // Create system event in ticket
            $ticket->items()->create([
                'type' => 'sla_breach',
                'body' => "Se ha incumplido el SLA de primera respuesta. Vencimiento: {$ticket->sla_first_response_due_at->format('d/m/Y H:i')}",
                'is_internal' => true,
                'sender_type' => 'system',
                'metadata' => [
                    'breach_type' => 'first_response',
                    'breach_duration_minutes' => $breachDuration,
                    'breach_id' => $breach->id,
                ],
            ]);

            // Broadcast event
            broadcast(new TicketSlaBreached($ticket, $breach));

            // Send notifications (to assignee, group members, and escalation recipients)
            $this->sendBreachNotifications($ticket, $breach);

            $breaches++;
        }

        return $breaches;
    }

    /**
     * Check for resolution SLA breaches
     */
    protected function checkResolutionBreaches(Carbon $now): int
    {
        $breaches = 0;

        // Find tickets that have breached resolution SLA
        $tickets = Ticket::whereNull('resolved_at')
            ->where('sla_resolution_breached', false)
            ->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', $now)
            ->where('sla_paused', false)
            ->with(['status', 'category', 'assignee', 'group', 'slaPolicy'])
            ->get();

        foreach ($tickets as $ticket) {
            $this->info("Ticket #{$ticket->ticket_number}: Incumplimiento de resolución");

            // Calculate breach duration
            $breachDuration = $ticket->sla_resolution_due_at->diffInMinutes($now);

            // Mark ticket as breached
            $ticket->update([
                'sla_resolution_breached' => true,
            ]);

            // Create breach record
            $breach = TicketSlaBreach::create([
                'ticket_id' => $ticket->id,
                'breach_type' => 'resolution',
                'due_at' => $ticket->sla_resolution_due_at,
                'breached_at' => $now,
                'breach_duration_minutes' => $breachDuration,
                'resolved' => false,
                'metadata' => [
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status->name,
                    'category' => $ticket->category->name,
                    'assignee' => $ticket->assignee?->name,
                    'group' => $ticket->group?->name,
                    'priority' => $ticket->priority,
                    'sla_policy' => $ticket->slaPolicy?->name,
                ],
            ]);

            // Create system event in ticket
            $ticket->items()->create([
                'type' => 'sla_breach',
                'body' => "Se ha incumplido el SLA de resolución. Vencimiento: {$ticket->sla_resolution_due_at->format('d/m/Y H:i')}",
                'is_internal' => true,
                'sender_type' => 'system',
                'metadata' => [
                    'breach_type' => 'resolution',
                    'breach_duration_minutes' => $breachDuration,
                    'breach_id' => $breach->id,
                ],
            ]);

            // Broadcast event
            broadcast(new TicketSlaBreached($ticket, $breach));

            // Send notifications
            $this->sendBreachNotifications($ticket, $breach);

            $breaches++;
        }

        return $breaches;
    }

    /**
     * Send notifications about SLA breach
     */
    protected function sendBreachNotifications(Ticket $ticket, TicketSlaBreach $breach): void
    {
        try {
            // Notify assignee
            if ($ticket->assignee) {
                $ticket->assignee->notify(
                    new \App\Notifications\Helpdesk\TicketSlaBreach($ticket, $breach)
                );
            }

            // Notify group members
            if ($ticket->group) {
                foreach ($ticket->group->members as $member) {
                    $member->notify(
                        new \App\Notifications\Helpdesk\TicketSlaBreach($ticket, $breach)
                    );
                }
            }

            // Notify escalation recipients if enabled in SLA policy
            if ($ticket->slaPolicy && $ticket->slaPolicy->enable_escalation) {
                $recipients = $ticket->slaPolicy->escalation_recipients ?? [];
                foreach ($recipients as $email) {
                    // Send email notification
                    \Illuminate\Support\Facades\Mail::to($email)->send(
                        new \Modules\Mail\Mail\Helpdesk\TicketSlaBreachMail($ticket, $breach)
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending SLA breach notifications', [
                'ticket_id' => $ticket->id,
                'breach_id' => $breach->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
