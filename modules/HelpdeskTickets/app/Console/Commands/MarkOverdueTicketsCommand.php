<?php

namespace Modules\HelpdeskTickets\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskTickets\Models\Ticket;

class MarkOverdueTicketsCommand extends Command
{
    protected $signature = 'ticket:autooverdue';

    protected $description = 'Mark tickets as SLA-breached when their due date has passed';

    public function handle(): int
    {
        try {
            $resolutionBreached = $this->markResolutionBreaches();
            $firstResponseBreached = $this->markFirstResponseBreaches();
            $nextResponseBreached = $this->markNextResponseBreaches();

            $total = $resolutionBreached + $firstResponseBreached + $nextResponseBreached;

            $this->info("Marked {$total} SLA breach(es): {$resolutionBreached} resolution, {$firstResponseBreached} first response, {$nextResponseBreached} next response.");
            Log::info('AutoOverdueTickets: SLA breaches marked.', [
                'resolution' => $resolutionBreached,
                'first_response' => $firstResponseBreached,
                'next_response' => $nextResponseBreached,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('AutoOverdueTickets command failed', ['error' => $e->getMessage()]);
            $this->error('Command failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function markResolutionBreaches(): int
    {
        return Ticket::query()
            ->whereNull('closed_at')
            ->where('sla_resolution_breached', false)
            ->whereNotNull('sla_resolution_due_at')
            ->where('sla_resolution_due_at', '<', now())
            ->update(['sla_resolution_breached' => true]);
    }

    private function markFirstResponseBreaches(): int
    {
        return Ticket::query()
            ->whereNull('first_response_at')
            ->where('sla_first_response_breached', false)
            ->whereNotNull('sla_first_response_due_at')
            ->where('sla_first_response_due_at', '<', now())
            ->update(['sla_first_response_breached' => true]);
    }

    private function markNextResponseBreaches(): int
    {
        return Ticket::query()
            ->whereNull('closed_at')
            ->where('sla_next_response_breached', false)
            ->whereNotNull('sla_next_response_due_at')
            ->where('sla_next_response_due_at', '<', now())
            ->update(['sla_next_response_breached' => true]);
    }
}
