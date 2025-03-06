<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Setting;
use App\Events\CronJobExecuted;
use App\Library\Log as MailLog;

class CronJobExecutedListener
{
    public function __construct()
    {
    }

    public function handle(CronJobExecuted $event)
    {
        Setting::set('cronjob_last_execution', \Carbon\Carbon::now()->timestamp);
    }

}
