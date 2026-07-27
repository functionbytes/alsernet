<?php

namespace Modules\Remarketing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Remarketing\Models\Campaign;
use Modules\Remarketing\Services\CampaignService;

class ProcessScheduledCampaignsCommand extends Command
{
    protected $signature = 'remarketing:process-scheduled-campaigns';

    protected $description = 'Send campaigns whose scheduled_at time has arrived';

    public function handle(CampaignService $campaignService): int
    {
        $campaigns = Campaign::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        $sent = 0;

        foreach ($campaigns as $campaign) {
            try {
                $campaignService->send($campaign);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('ProcessScheduledCampaignsCommand: failed to send campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$campaigns->count()} scheduled campaigns, sent {$sent}.");

        return self::SUCCESS;
    }
}
