<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Library\Contracts\CampaignInterface;
use Modules\Campaign\Library\Traits\Trackable;

class LoadCampaign implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Trackable;

    public $timeout = 7200;

    public $failOnTimeout = true;

    public $tries = 1;

    public $maxExceptions = 1;

    protected CampaignInterface $campaign;

    /**
     * Create a new job instance.
     */
    public function __construct(CampaignInterface $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            return;
        }

        $this->campaign->setSending();

        $loadLimit = 100 + rand(1, 9);
        $this->campaign->logger()->info(sprintf('Loading contacts to shoot (up to %s)', $loadLimit));
        Log::info('Campaign load batch started', [
            'campaign_uid' => $this->campaign->uid,
            'batch_limit' => $loadLimit,
        ]);

        $this->campaign->loadDeliveryJobs(function (ShouldQueue $deliveryJob) {
            $this->batch()->add($deliveryJob);
        }, $loadLimit);
    }
}
