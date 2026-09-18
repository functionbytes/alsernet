<?php

namespace Modules\Queue\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueListCommand extends Command
{
    protected $signature = 'queue:list';

    protected $description = 'List all configured queues with their current status';

    public function handle(): int
    {
        $queues = config('queue_module.known_queues', []);

        if (empty($queues)) {
            $this->warn('No queues configured in queue_module.known_queues.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($queues as $queue) {
            $pending = $this->getPendingCount($queue);
            $rows[] = [$queue, $pending, $pending > 0 ? 'Active' : 'Idle'];
        }

        $this->table(['Queue Name', 'Pending Jobs', 'Status'], $rows);

        return self::SUCCESS;
    }

    private function getPendingCount(string $queue): int
    {
        try {
            return DB::table('jobs')->where('queue', $queue)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
