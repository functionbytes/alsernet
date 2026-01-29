<?php

namespace Modules\Mailing\Jobs;

use Modules\Mailing\Models\Blacklist;
use Modules\Mailing\Models\MailList;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class UpdateMailListJob extends Base implements ShouldBeUnique
{
    public $list;

    public $uniqueFor = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(MailList $list)
    {
        $this->list = $list;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->list->updateCachedInfo();
        // blacklist new emails (if any)
        Blacklist::doBlacklist($this->list->customer);
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return $this->list->id;
    }
}
