<?php

namespace Modules\Mailing\Listeners;

use Modules\Mailing\Events\UserUpdated;

class UserUpdatedListener
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
    public function handle(UserUpdated $event)
    {
        dispatch(new \Acelle\Jobs\UpdateUserJob($event->customer));
    }
}
