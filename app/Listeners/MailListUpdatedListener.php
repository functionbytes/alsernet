<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\MailListUpdated;
use App\Jobs\UpdateMailListJob;

class MailListUpdatedListener
{

    public function __construct()
    {
    }

    public function handle(MailListUpdated $event)
    {
        dispatch(new UpdateMailListJob($event->mailList));
    }
}
