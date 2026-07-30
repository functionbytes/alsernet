<?php

namespace Modules\HelpdeskEmailLog\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\Setting;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;

class PruneEmailLogsCommand extends Command
{
    protected $signature = 'email-logs:prune
        {--days= : Override the configured retention period in days}
        {--stale-hours= : Override the configured "stale queued" threshold in hours}';

    protected $description = 'Delete old email log entries and mark stale queued entries as failed';

    public function handle(): int
    {
        $this->markStaleQueuedAsFailed();
        $this->deleteOldEntries();

        return self::SUCCESS;
    }

    private function markStaleQueuedAsFailed(): void
    {
        $hours = (int) ($this->option('stale-hours') ?? Setting::get('helpdeskemaillog.stale_queued_hours', config('helpdeskemaillog.stale_queued_hours', 0)));

        if ($hours <= 0) {
            return;
        }

        $marked = EmailLog::query()
            ->staleQueued($hours)
            ->update([
                'status' => EmailStatus::Failed->value,
                'failed_at' => now(),
                'error_message' => "No sending confirmation received within {$hours}h.",
            ]);

        if ($marked > 0) {
            Cache::forget('helpdeskemaillog:stats');
            $this->components->info("Marked {$marked} stale queued entr".($marked === 1 ? 'y' : 'ies').' as failed.');
        }
    }

    private function deleteOldEntries(): void
    {
        $days = (int) ($this->option('days') ?? Setting::get('helpdeskemaillog.retention_days', config('helpdeskemaillog.retention_days', 0)));

        if ($days <= 0) {
            $this->components->info('Retention is disabled (retention_days <= 0); nothing pruned.');

            return;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        do {
            $deleted = EmailLog::query()
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $total += $deleted;
        } while ($deleted > 0);

        if ($total > 0) {
            Cache::forget('helpdeskemaillog:stats');
            Cache::forget('helpdeskemaillog:modules');
        }

        $this->components->info("Pruned {$total} email log entr".($total === 1 ? 'y' : 'ies')." older than {$days} days.");
    }
}
