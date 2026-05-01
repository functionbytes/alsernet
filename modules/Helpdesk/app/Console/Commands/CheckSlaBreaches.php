<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Models\Conversation;

class CheckSlaBreaches extends Command
{
    protected $signature = 'helpdesk:check-sla';

    protected $description = 'Detect conversations exceeding the 15-minute first-response SLA and broadcast alerts';

    public function handle(): int
    {
        $conversations = Conversation::query()
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->whereNull('first_response_at')
            ->whereNull('sla_warned_at')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->get();

        foreach ($conversations as $conversation) {
            SlaBreached::dispatch($conversation);

            $conversation->timestamps = false;
            $conversation->sla_warned_at = now();
            $conversation->save();
        }

        $this->info("SLA check complete. {$conversations->count()} breach(es) dispatched.");

        return Command::SUCCESS;
    }
}
