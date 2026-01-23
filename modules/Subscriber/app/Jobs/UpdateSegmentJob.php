<?php

namespace Modules\Subscriber\Jobs;

use Modules\Core\Jobs\Base;

class UpdateSegmentJob extends Base
{
    protected $segment;

    public function __construct($segment)
    {
        $this->segment = $segment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->segment->updateCache();
    }
}
