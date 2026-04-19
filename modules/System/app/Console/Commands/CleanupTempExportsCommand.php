<?php

namespace Modules\System\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupTempExportsCommand extends Command
{
    protected $signature = 'exports:cleanup {--days=1 : Delete exports older than this many days}';

    protected $description = 'Delete temporary export files older than the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $threshold = now()->subDays($days)->getTimestamp();
        $deleted = 0;

        foreach (['exports/forms', 'exports/attentions', 'exports'] as $directory) {
            if (! Storage::disk('local')->exists($directory)) {
                continue;
            }

            foreach (Storage::disk('local')->files($directory) as $file) {
                $lastModified = Storage::disk('local')->lastModified($file);

                if ($lastModified < $threshold) {
                    Storage::disk('local')->delete($file);
                    $deleted++;
                }
            }
        }

        Log::info('Temp export cleanup completed', ['deleted' => $deleted, 'older_than_days' => $days]);
        $this->info("Deleted {$deleted} export file(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
