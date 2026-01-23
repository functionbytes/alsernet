<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

        // Update status
        $this->campaign->setSending();

        // Iterating through a big list ans create job objects may cause memory leak
        // So, LoadCampaign only loads a certain numbers subscribers each time, then just finish job to release the queue listener (remember to configure the queue correctly to release after a short time)
        // When a campaign is done, it will automaticall launch a new LoadCampaign job if there are more subscribers to send
        $loadLimit = 100 + rand(1, 9);
        $this->campaign->logger()->info(sprintf('Loading contacts to shoot (up to %s)', $loadLimit));

        // Iterate through contacts and launch sending process
        $this->campaign->loadDeliveryJobs(function (ShouldQueue $deliveryJob) {
            $this->batch()->add($deliveryJob);
        }, $loadLimit);
    }
}
