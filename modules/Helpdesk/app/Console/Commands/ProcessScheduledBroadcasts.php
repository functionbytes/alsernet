<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Jobs\SendBroadcastJob;
use Modules\Helpdesk\Models\Campaigns\Broadcast;

class ProcessScheduledBroadcasts extends Command
{
    protected $signature = 'helpdesk:process-broadcasts';

    protected $description = 'Process scheduled broadcasts that are due';

    public function handle(): int
    {
        $broadcasts = Broadcast::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($broadcasts as $broadcast) {
            $broadcast->update(['status' => 'sending']);
            SendBroadcastJob::dispatch($broadcast);
            Log::info("Dispatched broadcast #{$broadcast->id}");
        }

        $this->info("Processed {$broadcasts->count()} broadcast(s)");

        return Command::SUCCESS;
    }
}
