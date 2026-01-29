<?php

namespace Modules\Mailing\Jobs;

use Modules\Mailing\Library\Contracts\CampaignInterface;
use Modules\Mailing\Library\Traits\Trackable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
     *
     * @return void
     */
    public function __construct(CampaignInterface $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
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
