<?php

namespace Modules\Campaign\Jobs;

class UpdateAutomation extends Base
{
    protected $automation;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($automation)
    {
        $this->automation = $automation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->automation->mailList()->exists()) {
            $this->automation->updateCache();
        }
    }
}
