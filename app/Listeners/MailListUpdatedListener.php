<?php

namespace App\Listeners;

use App\Events\MailListUpdated;
use App\Jobs\UpdateMailListJob;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class MailListUpdatedListener
{

    public function __construct()
    {
        //
    }

    public function handle(MailListUpdated $event)
    {
        dispatch(new UpdateMailListJob($event->mailList));
    }
}
