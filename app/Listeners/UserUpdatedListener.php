<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\UserUpdated;
use App\Jobs\UpdateUserJob;

class UserUpdatedListener
{
    public function __construct()
    {
    }

    public function handle(UserUpdated $event)
    {
        dispatch(new UpdateUserJob($event->customer));
    }

}
