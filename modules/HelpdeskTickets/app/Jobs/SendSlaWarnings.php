<?php

namespace Modules\HelpdeskTickets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Events\SlaWarning;
use Modules\HelpdeskTickets\Models\Ticket;

/**
 * Job to send warnings for tickets approaching SLA deadline
 */
class SendSlaWarnings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public int $backoff = 60;

    public function __construct()
    {
        $this->queue = 'helpdesk-scheduled';
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('send-sla-warnings'))->dontRelease()];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendSlaWarnings job permanently failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('SendSlaWarnings job started at '.now());

            $warningWindow = now()->addMinutes(30);
            $thresholdPercent = config('helpdesk.sla.warning_threshold_percent', 80);

            $approachingTickets = Ticket::query()
                ->where('sla_resolution_breached', false)
                ->whereBetween('sla_resolution_due_at', [now(), $warningWindow])
                ->whereNotNull('sla_resolution_due_at')
                ->whereNull('closed_at')
                ->get();

            $warningCount = 0;

            foreach ($approachingTickets as $ticket) {
                try {
                    if (! $ticket->created_at || ! $ticket->sla_resolution_due_at) {
                        continue;
                    }

                    $totalTime = $ticket->created_at->diffInSeconds($ticket->sla_resolution_due_at);
                    $usedTime = $ticket->created_at->diffInSeconds(now());
                    $percentUsed = $totalTime > 0 ? ($usedTime / $totalTime) * 100 : 0;

                    if ($percentUsed >= $thresholdPercent) {
                        // El aviso lo envía el listener SendSlaWarningNotification
                        // (SlaWarningMail) suscrito a SlaWarning — no duplicar aquí.
                        event(new SlaWarning($ticket, $percentUsed));

                        Log::warning("SLA warning for ticket #{$ticket->id} - {$percentUsed}% time used", [
                            'ticket_id' => $ticket->id,
                            'subject' => $ticket->subject,
                            'due_at' => $ticket->sla_resolution_due_at,
                            'percent_used' => round($percentUsed, 2),
                            'threshold' => $thresholdPercent,
                        ]);

                        $warningCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send SLA warning for ticket #{$ticket->id}: {$e->getMessage()}", [
                        'ticket_id' => $ticket->id,
                        'exception' => $e,
                    ]);
                }
            }

            Log::info('SendSlaWarnings job completed at '.now()." - Total warnings sent: {$warningCount}");
        } catch (\Exception $e) {
            Log::error("SendSlaWarnings job failed: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
