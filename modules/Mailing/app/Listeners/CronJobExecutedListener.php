<?php

namespace Modules\Mailing\Listeners;

use Modules\Mailing\Events\CronJobExecuted;
use Modules\Mailing\Models\Setting;

class CronJobExecutedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CronJobExecuted $event)
    {
        Setting::set('cronjob_last_execution', \Carbon\Carbon::now()->timestamp);
    }
}
