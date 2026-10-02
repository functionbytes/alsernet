<?php

namespace Modules\Helpdesk\Console\Commands;

use Illuminate\Console\Command;
use Modules\Helpdesk\Events\SlaBreached;
use Modules\Helpdesk\Models\Conversation;
use Nwidart\Modules\Facades\Module;

class CheckSlaBreaches extends Command
{
    protected $signature = 'helpdesk:check-sla';

    protected $description = 'Legacy first-response SLA check. Superseded by helpdesksla:check-breaches when the HelpdeskSla module is enabled';

    public function handle(): int
    {
        // The HelpdeskSla module owns the real policy-based SLA engine
        // (helpdesksla:check-breaches). Bail out here to avoid double dispatch
        // and the legacy hardcoded 15-minute threshold.
        if (Module::find('HelpdeskSla')?->isEnabled()) {
            $this->info('SLA handled by HelpdeskSla module (helpdesksla:check-breaches). Skipping legacy check.');

            return self::SUCCESS;
        }

        $thresholdMinutes = (int) config('helpdesk.sla.legacy_first_response_minutes', 15);
        $dispatched = 0;

        Conversation::query()
            ->whereHas('status', fn ($q) => $q->where('is_open', true))
            ->whereNull('first_response_at')
            ->whereNull('sla_warned_at')
            ->where('created_at', '<=', now()->subMinutes($thresholdMinutes))
            ->chunkById(200, function ($conversations) use (&$dispatched): void {
                foreach ($conversations as $conversation) {
                    $conversation->timestamps = false;
                    $conversation->sla_warned_at = now();
                    $conversation->save();

                    SlaBreached::dispatch($conversation);
                    $dispatched++;
                }
            });

        $this->info("SLA check complete. {$dispatched} breach(es) dispatched.");

        return Command::SUCCESS;
    }
}
