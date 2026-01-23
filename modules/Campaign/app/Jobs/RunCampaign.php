<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Campaign\Library\Contracts\CampaignInterface;
use Modules\Campaign\Library\Traits\Trackable;

class RunCampaign implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Trackable;

    protected CampaignInterface $campaign;

    public $timeout = 300;

    public $failOnTimeout = true;

    public $tries = 1;

    public $maxExceptions = 1;

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
        if ($this->campaign->isPaused()) {
            return;
        }

        try {
            $this->campaign->logger()->info('Launch campaign ---------------------->');
            $this->campaign->run();
        } catch (\Throwable $e) {
            $errorMsg = 'Error scheduling campaign: '.$e->getMessage()."\n".$e->getTraceAsString();
            $this->campaign->setError($errorMsg);

            // To set the job to failed
            throw $e;
        }
    }
}
