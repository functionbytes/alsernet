<?php

namespace App\Events\Campaigns;

use App\Models\Newsletter\Newsletter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class GiftvoucherCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $newsletter;

    public function __construct(Newsletter $newsletter)
    {
        $this->newsletter = $newsletter;
    }


}
