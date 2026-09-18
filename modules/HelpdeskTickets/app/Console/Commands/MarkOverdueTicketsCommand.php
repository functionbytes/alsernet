<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\TicketSettings;

class MarkOverdueTicketsCommand extends Command
{
    protected $signature = 'ticket:autooverdue';

    protected $description = 'Mark tickets as SLA-breached when their due date has passed';

    public function handle(): int
    {
        try {
            $settings = app(TicketSettings::class);

            if (! $settings->boolean('auto_overdue_ticket', false)) {
                $this->info('Automatic SLA breach marking is disabled in Helpdesk settings.');

                return Command::SUCCESS;
            }

            $days = $settings->integer('auto_overdue_ticket_time', 5);
            $resolutionBreached = $this->markResolutionBreaches($days);
            $firstResponseBreached = $this->markFirstResponseBreaches($days);
            $nextResponseBreached = $this->markNextResponseBreaches($days);

            $total = $resolutionBreached + $firstResponseBreached + $nextResponseBreached;

            $this->info("Marked {$total} SLA breach(es): {$resolutionBreached} resolution, {$firstResponseBreached} first response, {$nextResponseBreached} next response.");
            Log::info('AutoOverdueTickets: SLA breaches marked.', [
                'resolution' => $resolutionBreached,
                'first_response' => $firstResponseBreached,
                'next_response' => $nextResponseBreached,
                'fallback_days' => $days,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('AutoOverdueTickets command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function markResolutionBreaches(int $fallbackDays): int
    {
        return Ticket::query()
            ->whereNull('closed_at')
            ->whereNull('sla_paused_at')
            ->where('sla_resolution_breached', false)
            ->where(function ($query) use ($fallbackDays): void {
                $query->where('sla_resolution_due_at', '<', now())
                    ->orWhere(function ($fallback) use ($fallbackDays): void {
                        $fallback->whereNull('sla_resolution_due_at')
                            ->where('created_at', '<', now()->subDays($fallbackDays));
                    });
            })
            ->update(['sla_resolution_breached' => true]);
    }

    private function markFirstResponseBreaches(int $fallbackDays): int
    {
        return Ticket::query()
            ->whereNull('first_response_at')
            ->whereNull('sla_paused_at')
            ->where('sla_first_response_breached', false)
            ->where(function ($query) use ($fallbackDays): void {
                $query->where('sla_first_response_due_at', '<', now())
                    ->orWhere(function ($fallback) use ($fallbackDays): void {
                        $fallback->whereNull('sla_first_response_due_at')
                            ->where('created_at', '<', now()->subDays($fallbackDays));
                    });
            })
            ->update(['sla_first_response_breached' => true]);
    }

    private function markNextResponseBreaches(int $fallbackDays): int
    {
        return Ticket::query()
            ->whereNull('closed_at')
            ->whereNull('sla_paused_at')
            ->where('sla_next_response_breached', false)
            ->where(function ($query) use ($fallbackDays): void {
                $query->where('sla_next_response_due_at', '<', now())
                    ->orWhere(function ($fallback) use ($fallbackDays): void {
                        $fallback->whereNull('sla_next_response_due_at')
                            ->where(function ($activity) use ($fallbackDays): void {
                                $activity->where('last_message_at', '<', now()->subDays($fallbackDays))
                                    ->orWhere(function ($noActivity) use ($fallbackDays): void {
                                        $noActivity->whereNull('last_message_at')
                                            ->where('created_at', '<', now()->subDays($fallbackDays));
                                    });
                            });
                    });
            })
            ->update(['sla_next_response_breached' => true]);
    }
}
