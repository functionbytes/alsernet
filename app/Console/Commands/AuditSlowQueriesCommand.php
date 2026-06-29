<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditSlowQueriesCommand extends Command
{
    protected $signature = 'app:audit-slow-queries {--threshold=200 : ms threshold}';

    protected $description = 'Loggea queries que toman más del threshold en ms durante 60s';

    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');
        $count = 0;

        DB::listen(function ($query) use ($threshold, &$count) {
            if ($query->time <= $threshold) {
                return;
            }

            $count++;
            Log::channel('slow-queries')->warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time_ms' => $query->time,
            ]);
        });

        $this->info("Listening for queries slower than {$threshold}ms... (run other commands or hit endpoints in another terminal)");
        $this->info('Press Ctrl+C to stop.');
        sleep(60);
        $this->info("Detected {$count} slow queries.");

        return self::SUCCESS;
    }
}
