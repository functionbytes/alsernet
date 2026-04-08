<?php

namespace Modules\Helpdesk\Console\Commands;

use App\Notifications\Helpdesk\TicketSlaWarning;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Ticket;
use Modules\Mail\Mail\Helpdesk\TicketSlaWarningMail;

class SendSlaWarnings extends Command
{
    protected $signature = 'tickets:sla-warnings {--threshold=80 : Porcentaje de umbral para advertencias}';

    protected $description = 'Enviar advertencias sobre SLAs próximos a vencer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $this->info("Verificando SLAs con umbral del {$threshold}%...");

        $now = Carbon::now();
        $warningsFound = 0;

        // Check first response SLA warnings
        $warningsFound += $this->checkFirstResponseWarnings($now, $threshold);

        // Check resolution SLA warnings
        $warningsFound += $this->checkResolutionWarnings($now, $threshold);

        $this->info("Se enviaron {$warningsFound} advertencias de SLA.");

        return 0;
    }

    /**
     * Check for first response SLA warnings
     */
    protected function checkFirstResponseWarnings(Carbon $now, int $threshold): int
    {
        $warnings = 0;

        // Find tickets approaching first response SLA
        $tickets = Ticket::whereNull('first_response_at')
            ->where('sla_first_response_breached', false)
            ->whereNotNull('sla_first_response_due_at')
            ->where('sla_first_response_due_at', '>', $now)
            ->where('sla_paused', false)
            ->with(['status', 'category', 'assignee', 'group', 'slaPolicy'])
            ->get();

        foreach ($tickets as $ticket) {
            // Calculate percentage of time elapsed
            $totalMinutes = $ticket->created_at->diffInMinutes($ticket->sla_first_response_due_at);
            $elapsedMinutes = $ticket->created_at->diffInMinutes($now);
            $percentageElapsed = ($elapsedMinutes / $totalMinutes) * 100;

            // Check if warning threshold is reached
            if ($percentageElapsed >= $threshold) {
                // Check if we've already sent a warning recently (last hour)
                $recentWarning = $ticket->items()
                    ->where('type', 'sla_warning')
                    ->where('created_at', '>', $now->copy()->subHour())
                    ->where('metadata->breach_type', 'first_response')
                    ->exists();

                if ($recentWarning) {
                    continue; // Skip if warning was sent recently
                }

                $this->info("Ticket #{$ticket->ticket_number}: Advertencia de primera respuesta ({$percentageElapsed}%)");

                $remainingMinutes = $now->diffInMinutes($ticket->sla_first_response_due_at);

                // Create warning event in ticket
                $ticket->items()->create([
                    'type' => 'sla_warning',
                    'body' => "El SLA de primera respuesta está próximo a vencer. Vencimiento: {$ticket->sla_first_response_due_at->format('d/m/Y H:i')} (en {$remainingMinutes} minutos)",
                    'is_internal' => true,
                    'sender_type' => 'system',
                    'metadata' => [
                        'breach_type' => 'first_response',
                        'percentage_elapsed' => round($percentageElapsed, 2),
                        'remaining_minutes' => $remainingMinutes,
                        'due_at' => $ticket->sla_first_response_due_at->toIso8601String(),
                    ],
                ]);

                // Broadcast event
                broadcast(new TicketSlaNearBreach($ticket, 'first_response', $remainingMinutes));

                // Send notifications
                $this->sendWarningNotifications($ticket, 'first_response', $remainingMinutes);

                $warnings++;
            }
        }

        return $warnings;
    }

    /**
     * Check for resolution SLA warnings
     */
    protected function checkResolutionWarnings(Carbon $now, int $threshold): int
    {
        $warnings = 0;

        // Find tickets approaching resolution SLA
        $tickets = Ticket::whereNull('resolved_at')
            ->where('sla_resolution_breached', false)
            ->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '>', $now)
            ->where('sla_paused', false)
            ->with(['status', 'category', 'assignee', 'group', 'slaPolicy'])
            ->get();

        foreach ($tickets as $ticket) {
            // Calculate percentage of time elapsed
            $totalMinutes = $ticket->created_at->diffInMinutes($ticket->sla_resolution_due_at);
            $elapsedMinutes = $ticket->created_at->diffInMinutes($now);
            $percentageElapsed = ($elapsedMinutes / $totalMinutes) * 100;

            // Check if warning threshold is reached
            if ($percentageElapsed >= $threshold) {
                // Check if we've already sent a warning recently (last 2 hours)
                $recentWarning = $ticket->items()
                    ->where('type', 'sla_warning')
                    ->where('created_at', '>', $now->copy()->subHours(2))
                    ->where('metadata->breach_type', 'resolution')
                    ->exists();

                if ($recentWarning) {
                    continue; // Skip if warning was sent recently
                }

                $this->info("Ticket #{$ticket->ticket_number}: Advertencia de resolución ({$percentageElapsed}%)");

                $remainingMinutes = $now->diffInMinutes($ticket->sla_resolution_due_at);

                // Create warning event in ticket
                $ticket->items()->create([
                    'type' => 'sla_warning',
                    'body' => "El SLA de resolución está próximo a vencer. Vencimiento: {$ticket->sla_resolution_due_at->format('d/m/Y H:i')} (en {$remainingMinutes} minutos)",
                    'is_internal' => true,
                    'sender_type' => 'system',
                    'metadata' => [
                        'breach_type' => 'resolution',
                        'percentage_elapsed' => round($percentageElapsed, 2),
                        'remaining_minutes' => $remainingMinutes,
                        'due_at' => $ticket->sla_resolution_due_at->toIso8601String(),
                    ],
                ]);

                // Broadcast event
                broadcast(new TicketSlaNearBreach($ticket, 'resolution', $remainingMinutes));

                // Send notifications
                $this->sendWarningNotifications($ticket, 'resolution', $remainingMinutes);

                $warnings++;
            }
        }

        return $warnings;
    }

    /**
     * Send warning notifications
     */
    protected function sendWarningNotifications(Ticket $ticket, string $type, int $remainingMinutes): void
    {
        try {
            // Notify assignee
            if ($ticket->assignee) {
                $ticket->assignee->notify(
                    new TicketSlaWarning($ticket, $type, $remainingMinutes)
                );
            }

            // Notify group members
            if ($ticket->group) {
                foreach ($ticket->group->members as $member) {
                    $member->notify(
                        new TicketSlaWarning($ticket, $type, $remainingMinutes)
                    );
                }
            }

            // If the SLA policy has escalation enabled and we're at critical threshold (>90%)
            if ($ticket->slaPolicy && $ticket->slaPolicy->enable_escalation) {
                $totalMinutes = $ticket->created_at->diffInMinutes(
                    $type === 'first_response' ? $ticket->sla_first_response_due_at : $ticket->sla_resolution_due_at
                );
                $elapsedMinutes = $totalMinutes - $remainingMinutes;
                $percentageElapsed = ($elapsedMinutes / $totalMinutes) * 100;

                $escalationThreshold = $ticket->slaPolicy->escalation_threshold_percent ?? 90;

                if ($percentageElapsed >= $escalationThreshold) {
                    $recipients = $ticket->slaPolicy->escalation_recipients ?? [];
                    foreach ($recipients as $email) {
                        Mail::to($email)->send(
                            new TicketSlaWarningMail($ticket, $type, $remainingMinutes)
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending SLA warning notifications', [
                'ticket_id' => $ticket->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
