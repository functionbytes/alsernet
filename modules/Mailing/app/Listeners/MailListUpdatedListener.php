<?php

namespace Modules\Mailing\Listeners;

use Modules\Mailing\Events\MailListUpdated;

class MailListUpdatedListener
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
    public function handle(MailListUpdated $event)
    {
        dispatch(new \Acelle\Jobs\UpdateMailListJob($event->mailList));
    }
}
