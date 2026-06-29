<?php

namespace Modules\Campaign\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\Core\Jobs\Base;

class UpdateCampaignJob extends Base implements ShouldBeUnique
{
    protected $campaign;

    public $uniqueFor = 3600;

    public function __construct($campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->campaign->updateCache();
    }

    public function uniqueId()
    {
        return $this->campaign->id;
    }
}
