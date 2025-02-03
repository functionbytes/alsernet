<?php

namespace App\Events\Campaigns;

use App\Models\Subscriber\Subscriber;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class GiftvoucherCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $newsletter;

    public function __construct(Subscriber $newsletter)
    {
        $this->newsletter = $newsletter;
    }


}
