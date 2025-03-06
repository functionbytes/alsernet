<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Models\User;
class SendNewUserNotification
{
    public function __construct()
    {
    }

    public function handle($event)
    {
    }

}
