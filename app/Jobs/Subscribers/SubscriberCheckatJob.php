<?php

namespace App\Jobs\Subscribers;

use App\Models\Subscriber\Subscriber;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriberCheckatJob
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $subscriber;

    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

}
