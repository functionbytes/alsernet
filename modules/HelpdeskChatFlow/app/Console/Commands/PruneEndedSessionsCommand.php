<?php

namespace Modules\HelpdeskChatFlow\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskChatFlow\Models\ChatFlowExecution;
use Modules\HelpdeskChatFlow\Models\ChatFlowSession;

/**
 * Purga sesiones ya terminadas (completed/failed/transferred/abandoned) y sus
 * executions asociadas. ExpireInactiveSessionsCommand solo marca abandonadas;
 * sin este prune las filas se acumulan indefinidamente.
 */
class PruneEndedSessionsCommand extends Command
{
    private const TERMINAL_STATUSES = ['completed', 'failed', 'transferred', 'abandoned'];

    protected $signature = 'chatflow:prune-sessions
        {--days= : Override the configured retention period in days}';

    protected $description = 'Delete ended chat flow sessions (and their executions) older than the retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('helpdeskchatflow.session_retention_days', 0));

        if ($days <= 0) {
            $this->components->info('Retention is disabled (session_retention_days <= 0); nothing pruned.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $ids = ChatFlowSession::query()
                ->whereIn('status', self::TERMINAL_STATUSES)
                ->whereNotNull('ended_at')
                ->where('ended_at', '<', $cutoff)
                ->limit(1000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            // executions.session_id no tiene FK con cascade: se borran a mano.
            ChatFlowExecution::query()->whereIn('session_id', $ids)->delete();
            $deleted = ChatFlowSession::query()->whereKey($ids)->delete();

            $total += $deleted;
        } while ($ids->count() === 1000);

        if ($total > 0) {
            Log::info('Pruned ended chat flow sessions', ['deleted' => $total, 'older_than_days' => $days]);
        }

        $this->components->info("Pruned {$total} ended chat flow session".($total === 1 ? '' : 's')." older than {$days} days.");

        return self::SUCCESS;
    }
}
