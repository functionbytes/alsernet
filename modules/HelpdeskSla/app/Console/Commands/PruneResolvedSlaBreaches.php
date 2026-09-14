<?php

namespace Modules\HelpdeskSla\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskSla\Models\ConversationSlaBreach;

class PruneResolvedSlaBreaches extends Command
{
    protected $signature = 'helpdesksla:prune-breaches
        {--days= : Override the configured retention period in days}';

    protected $description = 'Delete resolved SLA breach rows older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('helpdesksla.breach_retention_days', 0));

        if ($days <= 0) {
            $this->components->info('Retention is disabled (breach_retention_days <= 0); nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $deleted = ConversationSlaBreach::query()
                ->where('resolved', true)
                ->where('resolved_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        if ($total > 0) {
            Log::info('Pruned resolved SLA breaches', ['deleted' => $total, 'older_than_days' => $days]);
        }

        $this->components->info("Pruned {$total} resolved SLA breach row".($total === 1 ? '' : 's')." older than {$days} days.");

        return self::SUCCESS;
    }
}
